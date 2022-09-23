<?php

namespace Nuvei\Checkout\Controller\Payment\Callback;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Nuvei\Checkout\Model\Payment;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Nuvei\Checkout\Model\AbstractRequest;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;

/**
 * Nuvei Checkout payment redirect controller.
 */
class Dmn extends Action implements CsrfAwareActionInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var CaptureCommand
     */
    private $captureCommand;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;
    
    private $transaction;
    private $invoiceService;
    private $invoiceRepository;
    private $transObj;
    private $quoteFactory;
    private $request;
    private $orderRepo;
    private $searchCriteriaBuilder;
    private $orderResourceModel;
    private $requestFactory;
    private $httpRequest;
    
    // variables for the DMN process
    private $order;
    private $orderPayment;
    private $transactionType;
    private $sc_transaction_type;
    private $jsonOutput;
    private $paymentModel;
    private $registry;
    private $transactionRepository;
    private $start_subscr       = false;
    private $is_partial_settle  = false;
    private $curr_trans_info    = []; // collect the info for the current transaction (action)
    private $refund_msg         = '';

    /**
     * Object constructor.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Nuvei\Checkout\Model\Config $moduleConfig,
        \Magento\Sales\Model\Order\Payment\State\CaptureCommand $captureCommand,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transObj,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepo,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\ResourceModel\Order $orderResourceModel,
        \Nuvei\Checkout\Model\Request\Factory $requestFactory,
        \Magento\Framework\App\Request\Http $httpRequest,
        \Nuvei\Checkout\Model\Payment $paymentModel,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
        \Magento\Framework\Registry $registry // TODO Registry class is depricated
    ) {
        $this->moduleConfig             = $moduleConfig;
        $this->captureCommand           = $captureCommand;
        $this->dataObjectFactory        = $dataObjectFactory;
        $this->cartManagement           = $cartManagement;
        $this->jsonResultFactory        = $jsonResultFactory;
        $this->transaction              = $transaction;
        $this->invoiceService           = $invoiceService;
        $this->invoiceRepository        = $invoiceRepository;
        $this->transObj                 = $transObj;
        $this->quoteFactory             = $quoteFactory;
        $this->request                  = $request;
        $this->_eventManager            = $eventManager;
        $this->orderRepo                = $orderRepo;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->orderResourceModel       = $orderResourceModel;
        $this->requestFactory           = $requestFactory;
        $this->httpRequest              = $httpRequest;
        $this->paymentModel             = $paymentModel;
        $this->readerWriter             = $readerWriter;
        $this->registry                 = $registry;
        $this->transactionRepository    = $transactionRepository;
        
        parent::__construct($context);
    }
    
    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return JsonFactory
     */
    public function execute()
    {
        $this->jsonOutput = $this->jsonResultFactory->create();
        $this->jsonOutput->setHttpResponseCode(200);
        
        // set some variables
        $order_status   = '';
        $order_tr_type  = '';
        $last_record    = []; // last transaction data
        
        if (!$this->moduleConfig->isActive()) {
            $msg = 'DMN Error - Nuvei payment module is not active!';
            
            $this->readerWriter->createLog($msg);
            $this->jsonOutput->setData($msg);

            return $this->jsonOutput;
        }
        
        try {
            $params = array_merge(
                $this->request->getParams(),
                $this->request->getPostValue()
            );
            
            $this->readerWriter->createLog($params, 'DMN params:');
            
            if (!empty($params['type']) && 'CARD_TOKENIZATION' == $params['type']) {
                $msg = 'DMN report - this is Card Tokenization DMN.';
            
                $this->readerWriter->createLog($msg);
                $this->jsonOutput->setData($msg);

                return $this->jsonOutput;
            }
            
            ### DEBUG
//            $msg = 'DMN manually stopped.';
//            $this->readerWriter->createLog(http_build_query($params), $msg);
//            $this->jsonOutput->setData($msg);
//            return $this->jsonOutput;
            ### DEBUG
            
            // modify it because of the PayPal Sandbox problem with duplicate Orders IDs
            // we modify it also in Class PaymenAPM getParams().
            if (!empty($params['payment_method']) && 'cc_card' != $params['payment_method']) {
                $params["merchant_unique_id"] = $this->moduleConfig->getClientUniqueId($params["merchant_unique_id"]);
            }
            
            // try to find Order ID
            if (!empty($params["order"])) {
                $orderIncrementId = $params["order"];
            } elseif (!empty($params["merchant_unique_id"]) && (int) $params["merchant_unique_id"] != 0) {
                $orderIncrementId = $params["merchant_unique_id"];
            } elseif (!empty($params["orderId"])) {
                $orderIncrementId = $params["orderId"];
            } elseif (!empty($params['dmnType'])
                && in_array($params['dmnType'], ['subscriptionPayment', 'subscription'])
                && !empty($params['clientRequestId'])
                && false!== strpos($params['clientRequestId'], '_')
            ) {
                $orderIncrementId       = 0;
                $clientRequestId_arr    = explode('_', $params["clientRequestId"]);
                $last_elem              = end($clientRequestId_arr);
                
                if (!empty($last_elem) && is_numeric($last_elem)) {
                    $orderIncrementId = $last_elem;
                }
            } else {
                $msg = 'DMN error - no Order ID parameter.';
            
                $this->readerWriter->createLog($msg);
                $this->jsonOutput->setData($msg);

                return $this->jsonOutput;
            }
            // /try to find Order ID
            
            // try to validate the Cheksum
            $success = $this->validateChecksum($params, $orderIncrementId);
            
            if (!$success) {
                return $this->jsonOutput;
            }
            // /try to validate the Cheksum
            
            /**
             * Try to create the Order.
             * With this call if there are no errors we set:
             *
             * $this->order
             * $this->orderPayment
             */
            $success = $this->getOrCreateOrder($params, $orderIncrementId);
            
            if (!$success) {
                return $this->jsonOutput;
            }
            // /Try to create the Order.
            
            // last saved Additional Info for the transaction
            $ord_trans_addit_info = $this->orderPayment->getAdditionalInformation(Payment::ORDER_TRANSACTIONS_DATA);
            
            $this->readerWriter->createLog($ord_trans_addit_info, 'DMN $ord_trans_addit_info');
            
            if (empty($ord_trans_addit_info) || !is_array($ord_trans_addit_info)) {
                $ord_trans_addit_info = [];
            } else {
                $last_record    = end($ord_trans_addit_info);
                
                $order_status   = !empty($last_record[Payment::TRANSACTION_STATUS])
                    ? $last_record[Payment::TRANSACTION_STATUS] : '';
                
                $order_tr_type  = !empty($last_record[Payment::TRANSACTION_TYPE])
                    ? $last_record[Payment::TRANSACTION_TYPE] : '';
            }
            
            // prepare current transaction data for save
            $this->prepareCurrTrInfo($params);
            
            // check for Subscription State DMN
            $stop = $this->processSubscrDmn($params, $orderIncrementId, $ord_trans_addit_info);
            
            if ($stop) {
                $msg = 'Process Subscr DMN ends for order #' . $orderIncrementId;
                
                $this->readerWriter->createLog($msg);
                $this->jsonOutput->setData($msg);

                return $this->jsonOutput;
            }
            // /check for Subscription State DMN
            
            // try to find Status
            $status = !empty($params['Status']) ? strtolower($params['Status']) : null;
            
            if (!in_array($status, ['declined', 'error', 'approved', 'success'])) { // UNKNOWN DMN
                $msg = 'DMN for Order #' . $orderIncrementId . ' was not recognized.';
            
                $this->readerWriter->createLog($msg);
                $this->jsonOutput->setData($msg);

                return $this->jsonOutput;
            }
            // /try to find Status
            
            if (empty($params['transactionType'])) {
                $msg = 'DMN error - missing Transaction Type.';
            
                $this->readerWriter->createLog($msg);
                $this->jsonOutput->setData($msg);

                return $this->jsonOutput;
            }
            
            if (empty($params['TransactionID'])) {
                $msg = 'DMN error - missing Transaction ID.';
            
                $this->readerWriter->createLog($msg);
                $this->jsonOutput->setData($msg);

                return $this->jsonOutput;
            }
            
            $tr_type_param = strtolower($params['transactionType']);

            # Subscription transaction DMN
            if (!empty($params['dmnType'])
                && 'subscriptionPayment' == $params['dmnType']
                && !empty($params['TransactionID'])
            ) {
                $this->order->addStatusHistoryComment(
                    __('<b>Subscription Payment</b> with Status ') . $params['Status']
                        . __(' was made. Plan ID: ') . $params['planId']
                        . __(', Subscription ID: ') . $params['subscriptionId']
                        . __(', Amount: ') . $params['totalAmount'] . ' '
                        . $params['currency'] . __(', TransactionId: ') . $params['TransactionID']
                );
                
                $this->orderResourceModel->save($this->order);
                
                $msg = 'DMN process end for order #' . $orderIncrementId;
            
                $this->readerWriter->createLog($msg);
                $this->jsonOutput->setData($msg);

                return $this->jsonOutput;
            }
            # /Subscription transaction DMN
            
            // do not overwrite Order status
            $stop = $this->keepOrderStatusFromOverride($params, $order_tr_type, $order_status, $status);
            
            if ($stop) {
                return $this->jsonOutput;
            }
            // /do not overwrite Order status

            $parent_trans_id = isset($params['relatedTransactionId'])
                ? $params['relatedTransactionId'] : null;
            
            $this->orderPayment
                ->setTransactionId($params['TransactionID'])
                ->setParentTransactionId($parent_trans_id)
                ->setAuthCode($params['AuthCode']);
            
            if (!empty($params['payment_method'])) {
                $this->orderPayment->setAdditionalInformation(
                    Payment::TRANSACTION_PAYMENT_METHOD,
                    $params['payment_method']
                );
            }
            
            // compare them later
            $order_total    = round((float) $this->order->getBaseGrandTotal(), 2);
            $dmn_total      = round((float) $params['totalAmount'], 2);
            
            // PENDING TRANSACTION
            if ($status === "pending") {
                $this->order
                    ->setState(Order::STATE_NEW)
                    ->setStatus('pending');
            }
            
            // APPROVED TRANSACTION
            if (in_array($status, ['approved', 'success'])) {
                $this->sc_transaction_type = Payment::SC_PROCESSING;
                
                // try to recognize DMN type
                $this->processAuthDmn($params, $order_total, $dmn_total); // AUTH
                $this->processSaleAndSettleDMN($params, $order_total, $dmn_total, $last_record); // SALE and SETTLE
                $this->processVoidDmn($tr_type_param); // VOID
                $this->processRefundDmn($params); // REFUND/CREDIT
                
                $this->order->setStatus($this->sc_transaction_type);

                $msg_transaction = '<b>';
                
                if ($this->is_partial_settle === true) {
                    $msg_transaction .= __("Partial ");
                }
                
                $msg_transaction .= __($params['transactionType']) . ' </b> request.<br/>';

                $this->order->addStatusHistoryComment(
                    $msg_transaction
                        . __("Response status: ") . ' <b>' . $params['Status'] . '</b>.<br/>'
                        . __('Payment Method: ') . $params['payment_method'] . '.<br/>'
                        . __('Transaction ID: ') . $params['TransactionID'] . '.<br/>'
                        . __('Related Transaction ID: ') . $params['relatedTransactionId'] . '.<br/>'
                        . __('Transaction Amount: ') . number_format($params['totalAmount'], 2, '.', '')
                        . ' ' . $params['currency'] . '.'
                        . $this->refund_msg,
                    $this->sc_transaction_type
                );
            }
            
            // DECLINED/ERROR TRANSACTION
            if (in_array($status, ['declined', 'error'])) {
                $this->processDeclinedSaleOrSettleDmn($params);
                
                $params['ErrCode']      = (isset($params['ErrCode'])) ? $params['ErrCode'] : "Unknown";
                $params['ExErrCode']    = (isset($params['ExErrCode'])) ? $params['ExErrCode'] : "Unknown";
                
                $this->order->addStatusHistoryComment(
                    '<b>' . $params['transactionType'] . '</b> '
                        . __("request, response status is") . ' <b>' . $params['Status'] . '</b>.<br/>('
                        . __('Code: ') . $params['ErrCode'] . ', '
                        . __('Reason: ') . $params['ExErrCode'] . '.',
                    $this->sc_transaction_type
                );
            }
            
            $ord_trans_addit_info[] = $this->curr_trans_info;
        } catch (\Exception $e) {
            $msg = $e->getMessage();

            $this->readerWriter->createLog(
                $msg . "\n\r" . $e->getTraceAsString(),
                'DMN Excception:'
            );

            $this->jsonOutput->setData('Error: ' . $msg);
            $this->order->addStatusHistoryComment($msg);
        }
        
        $this->readerWriter->createLog('', 'DMN before finalSaveData()', 'DEBUG');
        
        $resp_save_data = $this->finalSaveData($ord_trans_addit_info);
        
        if (!$resp_save_data) {
            return $this->jsonOutput;
        }
        
        $this->readerWriter->createLog('DMN process end for order #' . $orderIncrementId);
        $this->jsonOutput->setData('DMN process end for order #' . $orderIncrementId);

        # try to create Subscription plans
        $resp = $this->createSubscription($params, $last_record, $orderIncrementId);

        return $this->jsonOutput;
    }
    
    /**
     * @param array $params
     * @param float $order_total
     * @param float $dmn_total
     */
    private function processAuthDmn($params, $order_total, $dmn_total)
    {
        if ('auth' != strtolower($params['transactionType'])) {
            return;
        }
        
        $this->sc_transaction_type = Payment::SC_AUTH;

        // amount check
        if ($order_total != $dmn_total) {
            $this->sc_transaction_type = 'fraud';

            $this->order->addStatusHistoryComment(
                __('<b>Attention!</b> - There is a problem with the Order. The Order amount is ')
                    . $this->order->getOrderCurrencyCode() . ' '
                    . $order_total . ', ' . __('but the Authorized amount is ')
                    . $params['currency'] . ' ' . $dmn_total,
                $this->sc_transaction_type
            );
        }
        
        // check for Zero Total Order with Rebilling
        $rebillling_data = json_decode($params['customField2'], true);
        
        if (0 == $order_total
            && !empty($rebillling_data)
            && is_array($rebillling_data)
        ) {
            $this->start_subscr = true;
            
            $this->order->addStatusHistoryComment(
                __("This is Zero Total Auth Transaction, you no need to Settle it."),
                $this->sc_transaction_type
            );
        }

        $this->orderPayment
            ->setAuthAmount($params['totalAmount'])
            ->setIsTransactionPending(true)
            ->setIsTransactionClosed(false);

        // set transaction
        $transaction = $this->transObj->setPayment($this->orderPayment)
            ->setOrder($this->order)
            ->setTransactionId($params['TransactionID'])
            ->setFailSafe(true)
            ->build(Transaction::TYPE_AUTH);

        $transaction->save();
    }
    
    /**
     * @param array     $params
     * @param float     $order_total
     * @param float     $dmn_total
     * @param array     $last_tr_record
     */
    private function processSaleAndSettleDMN($params, $order_total, $dmn_total, $last_tr_record)
    {
        $tr_type_param = strtolower($params['transactionType']);
        
        if (!in_array($tr_type_param, ['sale', 'settle']) || isset($params['dmnType'])) {
            return;
        }
        
        $this->readerWriter->createLog('processSaleAndSettleDMN()');
        
        $this->sc_transaction_type  = Payment::SC_SETTLED;
        $invCollection              = $this->order->getInvoiceCollection();
        $dmn_inv_id                 = $this->httpRequest->getParam('invoice_id');
        $is_cpanel_settle           = false;
        
        if (!empty($params["merchant_unique_id"])
            && $params["merchant_unique_id"] != $params["order"]
        ) {
            $is_cpanel_settle = true;
        }

        // set Start Subscription flag
        if ('sale' == $tr_type_param && !empty($params['customField2'])) {
            $this->start_subscr = true;
        } elseif ('settle' == $tr_type_param
            && !empty($last_tr_record)
            && !empty($last_tr_record['start_subscr_data'])
        ) {
            $this->start_subscr = true;
        }
        // set Start Subscription flag END
        
        if ($params["payment_method"] == 'cc_card') {
            $this->order->setCanVoidPayment(true);
            $this->orderPayment->setCanVoid(true);
        }
        
        // add Partial Settle flag
        if ('settle' == $tr_type_param
            && ($order_total - round(floatval($params['totalAmount']), 2) > 0.00)
        ) {
            $this->is_partial_settle = true;
        } elseif ($order_total != $dmn_total) { // amount check for Sale only
            $this->sc_transaction_type = 'fraud';

            $this->order->addStatusHistoryComment(
                __('<b>Attention!</b> - There is a problem with the Order. The Order amount is ')
                . $this->order->getOrderCurrencyCode() . ' '
                . $order_total . ', ' . __('but the Paid amount is ')
                . $params['currency'] . ' ' . $dmn_total,
                $this->sc_transaction_type
            );
        }

        // there are invoices
        if (count($invCollection) > 0 && !$is_cpanel_settle) {
            $this->readerWriter->createLog('There are Invoices');
            
            foreach ($invCollection as $invoice) {
                // Settle
                if ($dmn_inv_id == $invoice->getId()) {
                    $this->curr_trans_info['invoice_id'] = $invoice->getId();

                    $this->readerWriter->createLog([
                        '$dmn_inv_id' => $dmn_inv_id,
                        '$invoice->getId()' => $invoice->getId()
                    ]);
                    
                    $invoice->setCanVoidFlag(true);
                    $invoice
                        ->setTransactionId($params['TransactionID'])
                        ->setState(Invoice::STATE_PAID)
                        ->pay();
                    
                    $this->invoiceRepository->save($invoice);
                    
                    return;
                }
            }
            
            return;
        }
        
        // Force Invoice creation when we have CPanel Partial Settle
        if (!$this->order->canInvoice() && !$is_cpanel_settle) {
            $this->readerWriter->createLog('We can NOT create invoice.');
            return;
        }
        
        $this->readerWriter->createLog('There are no Invoices');
        
        // there are not invoices, but we can create
        if (
//            ( $this->order->canInvoice() || $is_cpanel_settle )
//            && (
            (
                'sale' == $tr_type_param // Sale flow
                || ( // APMs flow
                    $params["order"] == $params["merchant_unique_id"]
                    && $params["payment_method"] != 'cc_card'
                )
                || $is_cpanel_settle
            )
        ) {
            $this->readerWriter->createLog('We can create Invoice');
            
            $this->orderPayment
                ->setIsTransactionPending(0)
                ->setIsTransactionClosed(0);
            
            $invoice = $this->invoiceService->prepareInvoice($this->order);
            $invoice->setCanVoidFlag(true);
            
            $invoice
                ->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE)
                ->setTransactionId($params['TransactionID'])
                ->setState(Invoice::STATE_PAID);
            
            // in case of Cpanel Partial Settle
            if ($is_cpanel_settle && (float) $params['totalAmount'] < $order_total) {
                $order_total = round((float) $params['totalAmount'], 2);
            }
            
            $invoice
                ->setBaseSubtotal($this->order->getBaseSubtotal())
                ->setSubtotal($this->order->getSubtotal())
                ->setBaseGrandTotal($this->order->getBaseGrandTotal())
                ->setGrandTotal($this->order->getGrandTotal());
            
            $invoice->register();
            $invoice->getOrder()->setIsInProcess(true);
            $invoice->pay();
            
            $transactionSave = $this->transaction
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            
            $transactionSave->save();

            $this->curr_trans_info['invoice_id'] = $invoice->getId();

            // set transaction
            $transaction = $this->transObj
                ->setPayment($this->orderPayment)
                ->setOrder($this->order)
                ->setTransactionId($params['TransactionID'])
                ->setFailSafe(true)
                ->build(Transaction::TYPE_CAPTURE);

            $transaction->save();

//            $tr_type    = $this->orderPayment->addTransaction(Transaction::TYPE_CAPTURE);
//            $msg        = $this->orderPayment->prependMessage($message);
//
//            $this->orderPayment->addTransactionCommentsToOrder($tr_type, $msg);
            
            return;
        }
    }
    
    /**
     *
     * @param string $tr_type_param
     * @return void
     */
    private function processVoidDmn($tr_type_param)
    {
        if ('void' !=  $tr_type_param) {
            return;
        }
        
        $this->transactionType        = Transaction::TYPE_VOID;
        $this->sc_transaction_type    = Payment::SC_VOIDED;

        // set the Canceld Invoice
        $this->curr_trans_info['invoice_id'] = $this->httpRequest->getParam('invoice_id');

        // mark the Order Invoice as Canceld
        $invCollection = $this->order->getInvoiceCollection();

        $this->readerWriter->createLog(
            [
                'invoice_id'        => $this->curr_trans_info['invoice_id'],
                '$invCollection'    => count($invCollection)
            ],
            'Void DMN data:'
        );

        if (!empty($invCollection)) {
            foreach ($invCollection as $invoice) {
                $this->readerWriter->createLog($invoice->getId(), 'Invoice');

                if ($invoice->getId() == $this->curr_trans_info['invoice_id']) {
                    $this->readerWriter->createLog($invoice->getId(), 'Invoice to be Canceld');

                    $invoice->setState(Invoice::STATE_CANCELED);
                    $this->invoiceRepository->save($invoice);

                    break;
                }
            }
        }
        // mark the Order Invoice as Canceld END

        // Cancel active Subscriptions, if there are any
        $succsess = $this->paymentModel->cancelSubscription($this->orderPayment);

        // if we cancel any subscription set state Close
        if ($succsess) {
            $this->order->setData('state', Order::STATE_CLOSED);
        }
    }
    
    /**
     * @param array $params Incoming parameters.
     */
    private function processRefundDmn($params)
    {
        if (!in_array(strtolower($params['transactionType']), ['credit', 'refund'])) {
            return;
        }
        
        $this->readerWriter->createLog('', 'processRefundDmn', 'INFO');
        
        $this->transactionType        = Transaction::TYPE_REFUND;
        $this->sc_transaction_type    = Payment::SC_REFUNDED;

        if ((!empty($params['totalAmount']) && 'cc_card' == $params["payment_method"])
            || false !== strpos($params["merchant_unique_id"], 'gwp')
        ) {
            $this->refund_msg = '<br/>Refunded amount: '
                . number_format($params['totalAmount'], 2, '.', '') . ' ' . $params['currency'];
        }

        $this->curr_trans_info['invoice_id'] = $this->httpRequest->getParam('invoice_id');
    }
    
    /**
     * @param array $params the DMN parameters
     */
    private function processDeclinedSaleOrSettleDmn($params)
    {
        $this->readerWriter->createLog('processDeclinedSaleOrSettleDmn()');
        
        $invCollection  = $this->order->getInvoiceCollection();
        $dmn_inv_id     = (int) $this->httpRequest->getParam('invoice_id');
        
        try {
            if ('Settle' == $params['transactionType']) {
                $this->sc_transaction_type = Payment::SC_AUTH;
                
                foreach ($invCollection as $invoice) {
                    if ($dmn_inv_id == $invoice->getId()) {
                        $invoice
//                            ->setRequestedCaptureCase(Invoice::NOT_CAPTURE)
                            ->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE)
                            ->setTransactionId($params['TransactionID'])
//                            ->setState(Invoice::STATE_CANCELED);
                            ->setState(Invoice::STATE_PAID);
                        
                        $this->invoiceRepository->save($invoice);
                        
                        break;
                    }
                }
                
                
                // to enable Delete action
//                $this->registry->register('isSecureArea', true);
                
                // Delete the Invoice and the Transaction
//                $invoice_data = $this->invoiceRepository->get($dmn_inv_id);
                
//                if(!is_object($invoice_data)) {
//                    $this->readerWriter->createLog(
//                        'processDeclinedSaleOrSettleDmn() Error - $invoice_data is not an object.');
//
//                    return;
//                }
                
//                $transaction_id = $invoice_data->getTransactionId();
//                $transaction    = $invoice_data->getTransaction();
//                $transaction    = $this->transactionRepository->get($transaction_id);
////                $transaction    = $this->transactionRepository->getByTransactionId($transaction_id);
//
//                if(!$transaction) {
//                    $this->readerWriter->createLog(
//                        'processDeclinedSaleOrSettleDmn() Error - there is no $transaction.');
//
//                    return;
//                }
                
//                $this->transactionRepository->delete($transaction);
                
//                $this->invoiceRepository->delete($invoice_data);
            } elseif ('Sale' == $params['transactionType']) {
                $invCollection                          = $this->order->getInvoiceCollection();
                $invoice                                = current($invCollection);
                $this->curr_trans_info['invoice_id'][]  = $invoice->getId();
                $this->sc_transaction_type              = Payment::SC_CANCELED;

                $invoice
                    ->setTransactionId($params['TransactionID'])
                    ->setState(Invoice::STATE_CANCELED)
                ;
                
                $this->invoiceRepository->save($invoice);
            }
        } catch (\Exception $ex) {
            $this->readerWriter->createLog($ex->getMessage(), 'processDeclinedSaleOrSettleDmn() Exception.');
            return;
        }
        
        // there are invoices
//        if (count($invCollection) > 0) {
//            $this->readerWriter->createLog(count($invCollection), 'The Invoices count is');
//
//            foreach ($this->order->getInvoiceCollection() as $invoice) {
//                // Sale
//                if (0 == $dmn_inv_id) {
//                    $this->curr_trans_info['invoice_id'][] = $invoice->getId();
//
//                    $this->sc_transaction_type = Payment::SC_CANCELED;
//
//                    $invoice
//                        ->setTransactionId($params['TransactionID'])
//                        ->setState(Invoice::STATE_CANCELED)
//                        ->pay()
//                        ->save()
//                    ;
//
//
//
//                } elseif ($dmn_inv_id == $invoice->getId()) { // Settle
//                    $this->curr_trans_info['invoice_id'][] = $invoice->getId();
//
//                    $this->sc_transaction_type = Payment::SC_AUTH;
//
//                    $this->readerWriter->createLog('Declined Settle');
//
//                    $invoice
////                        ->setTransactionId($params['TransactionID'])
////                        ->setState(Invoice::STATE_CANCELED)
//                        ->setState(Invoice::STATE_OPEN)
////                        ->pay()
////                        ->save()
//                    ;
//
//                    $this->invoiceRepository->save($invoice);
//
//                    break;
//                }
//            }
//        }
    }
    
    /**
     * Work with Subscription status DMN.
     *
     * @param array $params
     * @param int   $orderIncrementId
     * @param array   $ord_trans_addit_info
     *
     * @return bool|string
     */
    private function processSubscrDmn($params, $orderIncrementId, $ord_trans_addit_info)
    {
        if (empty($params['dmnType'])
            || 'subscription' != $params['dmnType']
            || empty($params['subscriptionState'])
        ) {
            return false;
        }
        
        $this->readerWriter->createLog('processSubscrDmn()');
        
        if ('active' == strtolower($params['subscriptionState'])) {
            $this->order->addStatusHistoryComment(
                __("<b>Subscription</b> is Active. Subscription ID: ") . $params['subscriptionId']. ', '
                    . __('Plan ID: ') . $params['planId']. ', '
            );

            // Save the Subscription ID
            foreach (array_reverse($ord_trans_addit_info) as $key => $data) {
                if (!in_array(strtolower($data['transaction_type']), ['sale', 'settle', 'auth'])) {
                    $this->readerWriter->createLog($data['transaction_type'], 'processSubscrDmn() active continue');
                    continue;
                }

//                $subsc_ids = json_decode($data[Payment::SUBSCR_IDS]);
                
//                if (empty($subsc_ids)) {
//                    $subsc_ids = [];
//                } elseif (in_array($params['subscriptionId'], $subsc_ids)) {
//                    continue;
//                }

//                $subsc_ids[]                                        = $params['subscriptionId'];
//                $ord_trans_addit_info[$key][Payment::SUBSCR_IDS]    = json_encode($subsc_ids);
                $ord_trans_addit_info[$key][Payment::SUBSCR_IDS]    = $params['subscriptionId'];
                
                $this->orderPayment->setAdditionalInformation(
                    Payment::ORDER_TRANSACTIONS_DATA,
                    $ord_trans_addit_info
                );
                break;
            }
        }
        
        if ('inactive' == strtolower($params['subscriptionState'])) {
            $subscr_msg = __('<b>Subscription</b> is Inactive. ');

            if (!empty($params['subscriptionId'])) {
                $subscr_msg .= __('Subscription ID: ') . $params['subscriptionId'];
            }

            if (!empty($params['subscriptionId'])) {
                $subscr_msg .= __(', Plan ID: ') . $params['planId'];
            }

            $this->order->addStatusHistoryComment($subscr_msg);
        }
        
        if ('canceled' == strtolower($params['subscriptionState'])) {
            $this->order->addStatusHistoryComment(
                __('<b>Subscription</b> was canceled. ') . '<br/>'
                . __('<b>Subscription ID:</b> ') . $params['subscriptionId']
            );
        }
        
        $this->orderPayment->save();
        $this->orderResourceModel->save($this->order);
        $this->readerWriter->createLog($this->order->getStatus(), 'Process Subscr DMN Order Status', 'DEBUG');
        
        return true;
    }

    /**
     * Place order.
     *
     * @param array $params
     */
    private function placeOrder($params)
    {
        $this->readerWriter->createLog($params, 'PlaceOrder()');
        
        $result = $this->dataObjectFactory->create();
        
        if (empty($params['quote'])) {
            return $result
                ->setData('error', true)
                ->setData('message', 'Missing Quote parameter.');
        }
        
        try {
            $quote = $this->quoteFactory->create()->loadByIdWithoutStore((int) $params['quote']);
            
            if (!is_object($quote)) {
                $this->readerWriter->createLog($quote, 'placeOrder error - the quote is not an object.');

                return $result
                    ->setData('error', true)
                    ->setData('message', 'The quote is not an object.');
            }
            
            $method = $quote->getPayment()->getMethod();
            
            $this->readerWriter->createLog(
                [
                    'quote payment Method'  => $method,
                    'quote id'              => $quote->getEntityId(),
                    'quote is active'       => $quote->getIsActive(),
                    'quote reserved ord id' => $quote->getReservedOrderId(),
                ],
                'Quote data'
            );

            if ((int) $quote->getIsActive() == 0) {
                $this->readerWriter->createLog($quote->getQuoteId(), 'Quote ID');

                return $result
                    ->setData('error', true)
                    ->setData('message', 'Quote is not active.');
            }

            if ($method !== Payment::METHOD_CODE) {
                return $result
                    ->setData('error', true)
                    ->setData('message', 'Quote payment method is "' . $method . '"');
            }

//            $params = array_merge(
//                $this->request->getParams(),
//                $this->request->getPostValue()
//            );
            
            $orderId = $this->cartManagement->placeOrder($params);

            $result
                ->setData('success', true)
                ->setData('order_id', $orderId);

            $this->_eventManager->dispatch(
                'nuvei_place_order',
                [
                    'result' => $result,
                    'action' => $this,
                ]
            );
        } catch (\Exception $exception) {
            $this->readerWriter->createLog($exception->getMessage(), 'DMN placeOrder Exception: ');
            
            return $result
                ->setData('error', true)
                ->setData('message', $exception->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Function validateChecksum
     *
     * @param array $params
     * @param string $orderIncrementId
     *
     * @return mixed
     */
    private function validateChecksum($params, $orderIncrementId)
    {
        if (empty($params["advanceResponseChecksum"]) && empty($params['responsechecksum'])) {
            $msg = 'Required keys advanceResponseChecksum and '
                . 'responsechecksum for checksum calculation are missing.';
                
            $this->readerWriter->createLog($msg);
            $this->jsonOutput->setData($msg);
            
            return false;
        }
        
        // most of the DMNs with advanceResponseChecksum
        if (!empty($params["advanceResponseChecksum"])) {
            $concat     = $this->moduleConfig->getMerchantSecretKey();
            $params_arr = ['totalAmount', 'currency', 'responseTimeStamp', 'PPP_TransactionID', 'Status', 'productId'];

            foreach ($params_arr as $checksumKey) {
                if (!isset($params[$checksumKey])) {
                    $msg = 'Required key '. $checksumKey .' for checksum calculation is missing.';

                    $this->readerWriter->createLog($msg);
                    $this->jsonOutput->setData($msg);
                    
                    return false;
                }

                if (is_array($params[$checksumKey])) {
                    foreach ($params[$checksumKey] as $subVal) {
                        $concat .= $subVal;
                    }
                } else {
                    $concat .= $params[$checksumKey];
                }
            }

            $checksum = hash($this->moduleConfig->getHash(), $concat);

            if ($params["advanceResponseChecksum"] !== $checksum) {
                $msg = 'Checksum validation failed for advanceResponseChecksum and Order #' . $orderIncrementId;

                if ($this->moduleConfig->isTestModeEnabled() && null !== $this->order) {
                    $this->order->addStatusHistoryComment(__($msg)
                        . ' ' . __('Transaction type ') . $params['type']);
                }
                
                $this->readerWriter->createLog($msg);
                $this->jsonOutput->setData($msg);

                return false;
            }

            return true;
        }
        
        // subscription DMN with responsechecksum
        $param_responsechecksum = $params['responsechecksum'];
        unset($params['responsechecksum']);
        
        $concat = implode('', $params);
        
        if (empty($concat)) {
            $msg = 'Checksum string before hash is empty for Order #' . $orderIncrementId;
            
            $this->readerWriter->createLog($msg);
            $this->jsonOutput->setData($msg);

            return false;
        }
        
        $concat_final   = $concat . $this->moduleConfig->getMerchantSecretKey();
        $checksum       = hash($this->moduleConfig->getHash(), $concat_final);

        if ($param_responsechecksum !== $checksum) {
            $msg = 'Checksum validation failed for responsechecksum and Order #' . $orderIncrementId;

            if ($this->moduleConfig->isTestModeEnabled() && null !== $this->order) {
                $this->order->addStatusHistoryComment(__($msg)
                    . ' ' . __('Transaction type ') . $params['type']);
            }
            
            $this->readerWriter->createLog($msg);
            $this->jsonOutput->setData($msg);

            return false;
        }
        
        return true;
    }
    
    /**
     * Try to create Subscriptions.
     *
     * @param array $params
     * @param array $last_record
     * @param int   $orderIncrementId
     *
     * @return bool
     */
    
    private function createSubscription($params, $last_record, $orderIncrementId)
    {
        $this->readerWriter->createLog($this->start_subscr, 'createSubscription()');
        
        // no need to create a Subscription
        if (!$this->start_subscr) {
            return false;
        }
            
        $subscr_data   = json_decode($params['customField2'], true);
        $items_list   = json_decode($params['customField5'], true);
        $subsc_data     = [];
//        $subscr_count   = 0;

        // we allow only one Product in the Order to be with Payment Plan,
        // so the list with the products must be with length = 1
        if (!empty($subscr_data) && is_array($subscr_data)) {
//            $subsc_data = current($subscr_data);
            $subsc_data = $subscr_data;
        } elseif (!empty($last_record[Payment::TRANSACTION_UPO_ID])
            && is_numeric($last_record[Payment::TRANSACTION_UPO_ID])
        ) {
//            $subsc_data = current($last_record['start_subscr_data']);
            $subsc_data = $last_record['start_subscr_data'];
        }
        
        if (empty($subsc_data) || !is_array($subsc_data)) {
            $this->readerWriter->createLog($subsc_data, 'createSubscription() problem with the subscription data.');
            return false;
        }

        // we create as many Subscriptions as the Product quantity is
//        if (!empty($items_list) && is_array($items_list)) {
//            $customField5_curr = current($items_list);
//
//            if (isset($customField5_curr['quantity']) && is_numeric($customField5_curr['quantity'])) {
//                $subscr_count = (int) $customField5_curr['quantity'];
//            }
//        } else {
//            $items = $this->order->getAllItems();
//
//            foreach ($items as $item) {
//                $subscr_count += $item->getQtyOrdered();
//            }
//        }

        // Error - missing Subscription details
//        if (empty($subsc_data) || 0 == $subscr_count) {
//            $this->readerWriter->createLog(
//                [
//                    'subsc_data'    => $subsc_data,
//                    'subscr_count'  => $subscr_count,
//                ],
//                'DMN Error - can not create Subscription beacuse of missing data:'
//            );
//
//            return false;
//        }
        
        // create subscriptions for each of the Products
        $request = $this->requestFactory->create(AbstractRequest::CREATE_SUBSCRIPTION_METHOD);
        
//        do {
            $subsc_data['userPaymentOptionId'] = $params['userPaymentOptionId'];
            $subsc_data['userTokenId']         = $params['email'];
            $subsc_data['currency']            = $params['currency'];
            
        try {
            $params = array_merge(
                $this->request->getParams(),
                $this->request->getPostValue()
            );

            $resp = $request
                ->setOrderId($orderIncrementId)
                ->setData($subsc_data)
                ->process();

            // add note to the Order - Success
            if ('success' == strtolower($resp['status'])) {
                $msg =  __("<b>Subscription</b> was created. Subscription ID "
                    . $resp['subscriptionId']). '. '
                    . __('Recurring amount: ') . $params['currency'] . ' '
                    . $subsc_data['recurringAmount'];
            } else { // Error, Decline
                $msg = __("<b>Error</b> when try to create Subscription by this Order. ");

                if (!empty($resp['reason'])) {
                    $msg .= '<br/>' . __('Reason: ') . $resp['reason'];
                }
            }

            $this->order->addStatusHistoryComment($msg, $this->sc_transaction_type);
            $this->orderResourceModel->save($this->order);
        } catch (PaymentException $e) {
            $this->readerWriter->createLog('createSubscription - Error: ' . $e->getMessage());
        }
            
//            $subscr_count--;
//        } while ($subscr_count > 0);
        
        return true;
    }
    
    private function getOrCreateOrder($params, $orderIncrementId)
    {
        $this->readerWriter->createLog($orderIncrementId, 'getOrCreateOrder for $orderIncrementId');
        
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $orderIncrementId, 'eq')->create();

        $tryouts    = 0;
        $max_tries  = 5;
        
        // search only once for Refund/Credit
        if (isset($params['transactionType'])
            && in_array(strtolower($params['transactionType']), ['refund', 'credit'])
        ) {
            $max_tries = 0;
        }
        
        // do not search more than once for Auth and Sale, if the DMN response time is more than 24 hours before now
        if ($max_tries > 0
            && isset($params['transactionType'])
            && in_array(strtolower($params['transactionType']), ['sale', 'auth'])
            && !empty($params['customField4'])
            && is_numeric($params['customField4'])
            && time() - $params['customField4'] > 3600
        ) {
            $max_tries = 0;
        }
        
        do {
            $tryouts++;
            $orderList = $this->orderRepo->getList($searchCriteria)->getItems();

            if (!$orderList || empty($orderList)) {
                $this->readerWriter->createLog('DMN try ' . $tryouts
                    . ' there is NO order for TransactionID ' . $params['TransactionID'] . ' yet.');
                sleep(3);
            }
        } while ($tryouts < $max_tries && empty($orderList));
        
        // try to create the order
        if ((!$orderList || empty($orderList))
            && !isset($params['dmnType'])
        ) {
            if (in_array(strtolower($params['transactionType']), ['sale', 'auth'])
                && strtolower($params['Status']) != 'approved'
            ) {
                $msg = 'The Order ' . $orderIncrementId .' is not approved, stop process.';
                
                $this->readerWriter->createLog($msg);
                $this->jsonOutput->setData($msg);
                
                return false;
            }
            
            $this->readerWriter->createLog('Order '. $orderIncrementId .' not found, try to create it!');

            $result = $this->placeOrder($params);

            if ($result->getSuccess() !== true) {
                $msg = 'DMN Callback error - place order error.';
                
                $this->readerWriter->createLog($result->getMessage(), $msg);
                $this->jsonOutput->setData($msg);
                
                return false;
            }

            $orderList = $this->orderRepo->getList($searchCriteria)->getItems();

            $this->readerWriter->createLog('An Order with ID '. $orderIncrementId .' was created in the DMN page.');
        }
        
        if (!$orderList || empty($orderList)) {
            $msg = 'DMN Callback error - there is no Order and the code did not success to create it.';
            
            $this->readerWriter->createLog($msg);
            $this->jsonOutput->setData($msg);

            return false;
        }
        
        $this->order = current($orderList);
        
        if (null === $this->order) {
            $msg = 'DMN error - Order object is null.';
            
            $this->readerWriter->createLog($orderList, $msg);
            $this->jsonOutput->setData($msg);

            return false;
        }
        
        $this->orderPayment = $this->order->getPayment();
        
        if (null === $this->orderPayment) {
            $msg = 'DMN error - Order Payment object is null.';
            
            $this->readerWriter->createLog($msg);
            $this->jsonOutput->setData($msg);

            return false;
        }
        
        // check if the Order belongs to nuvei
        $method = $this->orderPayment->getMethod();

        if ('nuvei' != $method) {
            $msg = 'DMN getOrCreateOrder() error - the order was not made with Nuvei module.';
            
            $this->readerWriter->createLog([
                'orderIncrementId' => $orderIncrementId,
                'module' => $method,
            ], $msg);
            $this->jsonOutput->setData($msg);

            return false;
        }
        
        return true;
    }
    
    /**
     * @param array $params
     */
    private function prepareCurrTrInfo($params)
    {
        $this->curr_trans_info = [
            Payment::TRANSACTION_ID             => '',
            Payment::TRANSACTION_AUTH_CODE      => '',
            Payment::TRANSACTION_STATUS         => '',
            Payment::TRANSACTION_TYPE           => '',
            Payment::TRANSACTION_UPO_ID         => '',
            Payment::TRANSACTION_TOTAL_AMOUN    => '',
            Payment::TRANSACTION_PAYMENT_METHOD => '',
            Payment::SUBSCR_IDS                 => '',
            'start_subscr_data'                 => '',
        ];

        // some subscription DMNs does not have TransactionID
        if (isset($params['TransactionID'])) {
            $this->curr_trans_info[Payment::TRANSACTION_ID] = $params['TransactionID'];
        }
        if (isset($params['AuthCode'])) {
            $this->curr_trans_info[Payment::TRANSACTION_AUTH_CODE] = $params['AuthCode'];
        }
        if (isset($params['Status'])) {
            $this->curr_trans_info[Payment::TRANSACTION_STATUS] = $params['Status'];
        }
        if (isset($params['transactionType'])) {
            $this->curr_trans_info[Payment::TRANSACTION_TYPE] = $params['transactionType'];
        }
        if (isset($params['userPaymentOptionId'])) {
            $this->curr_trans_info[Payment::TRANSACTION_UPO_ID] = $params['userPaymentOptionId'];
        }
        if (isset($params['totalAmount'])) {
            $this->curr_trans_info[Payment::TRANSACTION_TOTAL_AMOUN] = $params['totalAmount'];
        }
        if (isset($params['payment_method'])) {
            $this->curr_trans_info[Payment::TRANSACTION_PAYMENT_METHOD] = $params['payment_method'];
        }
        if (!empty($params['customField2'])) {
            $this->curr_trans_info['start_subscr_data'] = $params['customField2'];
        }
    }
    
    /**
     * Help method keeping Order status from override with
     * delied or duplicated DMNs.
     *
     * @param array $params
     * @param string $order_tr_type
     * @param string $order_status
     *
     * return bool
     */
    private function keepOrderStatusFromOverride($params, $order_tr_type, $order_status, $status)
    {
        $tr_type_param = strtolower($params['transactionType']);
        
        // default - same transaction type, order was approved, but DMN status is different
        if (strtolower($order_tr_type) == $tr_type_param
            && strtolower($order_status) == 'approved'
            && $order_status != $params['Status']
        ) {
            $msg = 'Current Order status is "'. $order_status .'", but incoming DMN status is "'
                . $params['Status'] . '", for Transaction type '. $order_tr_type
                .'. Do not apply DMN data on the Order!';

            $this->readerWriter->createLog($msg);
            $this->jsonOutput->setData($msg);
        
            return true;
        }

        /**
         * When all is same for Sale
         * we do this check only for sale, because Settle, Reffund and Void
         * can be partial
         */
        if (strtolower($order_tr_type) == $tr_type_param
            && $tr_type_param == 'sale'
            && strtolower($order_status) == 'approved'
            && $order_status == $params['Status']
        ) {
            $msg = 'Duplicated Sale DMN. Stop DMN process!';
            
            $this->readerWriter->createLog($msg);
            $this->jsonOutput->setData($msg);
        
            return true;
        }

        // do not override status if the Order is Voided or Refunded
        if ('void' == strtolower($order_tr_type)
            && strtolower($order_status) == 'approved'
            && (strtolower($params['transactionType']) != 'void'
                || 'approved' != $status)
        ) {
            $msg = 'No more actions are allowed for order #' . $this->order->getId();
            
            $this->readerWriter->createLog($msg);
            $this->jsonOutput->setData($msg);
        
            return true;
        }

        // after Refund allow only refund, this is in case of Partial Refunds
        if (in_array(strtolower($order_tr_type), ['refund', 'credit'])
            && strtolower($order_status) == 'approved'
            && !in_array(strtolower($params['transactionType']), ['refund', 'credit'])
        ) {
            $msg = 'No more actions are allowed for order #' . $this->order->getId();
            
            $this->readerWriter->createLog($msg);
            $this->jsonOutput->setData($msg);
        
            return true;
        }

        if ($tr_type_param === 'auth' && strtolower($order_tr_type) === 'settle') {
            $msg = 'Can not set Auth to Settled Order #' . $this->order->getId();
            
            $this->readerWriter->createLog($msg);
            $this->jsonOutput->setData($msg);
        
            return true;
        }
        
        return false;
    }
    
    /**
     *
     * @param array $ord_trans_addit_info
     * @param int $tries
     *
     * @return boolean
     */
    private function finalSaveData($ord_trans_addit_info, $tries = 0)
    {
        $this->readerWriter->createLog('', 'finalSaveData()', 'INFO');
        
        if ($tries > 0) {
            $this->readerWriter->createLog($tries, 'DMN save Order data recursive retry.');
        }
        
        if ($tries > 5) {
            $this->readerWriter->createLog($tries, 'DMN save Order data maximum recursive retries reached.');
            $this->jsonOutput->setData('DMN save Order data maximum recursive retries reached.');
//            exit('DMN save Order data maximum recursive retries reached.');
            return false;
        }
        
        try {
            $this->readerWriter->createLog($ord_trans_addit_info, 'DMN before save $ord_trans_addit_info', 'DEBUG');
            
            $this->orderPayment
                ->setAdditionalInformation(Payment::ORDER_TRANSACTIONS_DATA, $ord_trans_addit_info)
                ->save();

            $this->readerWriter->createLog('DMN after save $ord_trans_addit_info', 'DEBUG');

            $this->orderResourceModel->save($this->order);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            
            $this->readerWriter->createLog($e->getMessage(), 'DMN save Order data exception.');
            
            if (strpos($msg, 'Deadlock found') !== false) {
                $tries++;
                sleep(1);
                $this->finalSaveData($ord_trans_addit_info, $tries);
            }
        }
        
        return true;
    }
}
