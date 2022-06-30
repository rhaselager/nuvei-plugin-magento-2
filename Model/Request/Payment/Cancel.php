<?php

namespace Nuvei\Checkout\Model\Request\Payment;

use Magento\Framework\Exception\PaymentException;
use Nuvei\Checkout\Model\AbstractRequest;
use Nuvei\Checkout\Model\AbstractResponse;
use Nuvei\Checkout\Model\Payment;
use Nuvei\Checkout\Model\Request\AbstractPayment;
use Nuvei\Checkout\Model\RequestInterface;

/**
 * Nuvei Checkout void payment request model.
 */
class Cancel extends AbstractPayment implements RequestInterface
{
    /**
     * Refund constructor.
     *
     * @param Config                            $config
     * @param Curl                              $curl
     * @param RequestFactory                    $requestFactory
     * @param Factory                           $paymentRequestFactory
     * @param ResponseFactory                   $responseFactory
     * @param OrderPayment                      $orderPayment
     * @param TransactionRepositoryInterface    $transactionRepository
     * @param Http                              $request
     * @param ReaderWriter                      $readerWriter
     * @param float                             $amount
     */
    public function __construct(
        \Nuvei\Checkout\Model\Config $config,
        \Nuvei\Checkout\Lib\Http\Client\Curl $curl,
        \Nuvei\Checkout\Model\Request\Factory $requestFactory,
        \Nuvei\Checkout\Model\Request\Payment\Factory $paymentRequestFactory,
        \Nuvei\Checkout\Model\Response\Factory $responseFactory,
        \Magento\Sales\Model\Order\Payment $orderPayment,
//        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Framework\App\Request\Http $request,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter,
        $amount = 0.0
    ) {
        parent::__construct(
            $config,
            $curl,
            $requestFactory,
            $paymentRequestFactory,
            $responseFactory,
            $orderPayment,
            $amount
        );

//        $this->transactionRepository    = $transactionRepository;
        $this->request                  = $request;
    }
    
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return AbstractRequest::PAYMENT_VOID_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractResponse::PAYMENT_VOID_HANDLER;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getParams()
    {
        // we can create Void for Settle and Auth only!!!
        $orderPayment           = $this->orderPayment;
        $ord_trans_addit_info   = $orderPayment->getAdditionalInformation(Payment::ORDER_TRANSACTIONS_DATA);
        $order                  = $orderPayment->getOrder();
        $inv_id                 = $this->request->getParam('invoice_id');
        $trans_to_void_data     = [];
        $last_voidable          = [];
        
        if (is_array($ord_trans_addit_info) && !empty($ord_trans_addit_info)) {
            foreach (array_reverse($ord_trans_addit_info) as $key => $trans) {
                if (strtolower($trans[Payment::TRANSACTION_STATUS]) == 'approved'
                    && in_array(strtolower($trans[Payment::TRANSACTION_TYPE]), ['auth', 'settle', 'sale'])
                ) {
                    if (0 == $key) {
                        $last_voidable = $trans;
                    }
                    
                    // settle
                    if (!empty($trans['invoice_id'])
                        && !empty($inv_id)
                        && $trans['invoice_id'] == $inv_id
                    ) {
                        $trans_to_void_data = $trans;
                        break;
                    }
                }
            }
        }
        
        /**
         * there was not settle Transaction, or we can not find transaction
         * based on Invoice ID. In this case use last voidable transaction.
         */
        if (empty($trans_to_void_data)) {
            $trans_to_void_data = $last_voidable;
        }
        
        if (empty($trans_to_void_data)) {
            $this->readerWriter->createLog(
                [
                    '$ord_trans_addit_info' => $ord_trans_addit_info,
                    '$trans_to_void_data'    => $trans_to_void_data,
                ],
                'Void Error - Missing mandatory data for the Void.'
            );
            
            throw new PaymentException(__('Void Error - Missing mandatory data for the Void.'));
        }
        
        $this->readerWriter->createLog($trans_to_void_data, 'Transaction to Cancel');
        
        $amount     = (float) $trans_to_void_data[Payment::TRANSACTION_TOTAL_AMOUN];
        $auth_code  = !empty($trans_to_void_data[Payment::TRANSACTION_AUTH_CODE])
            ? $trans_to_void_data[Payment::TRANSACTION_AUTH_CODE] : '';
        
        if (empty($amount) || $amount < 0) {
            $this->readerWriter->createLog(
                $trans_to_void_data,
                'Void error - Transaction does not contain total amount.'
            );
            
            throw new PaymentException(__('Void error - Transaction does not contain total amount.'));
        }
        
        $params = [
            'clientUniqueId'        => $order->getIncrementId(),
            'currency'              => $order->getBaseCurrencyCode(),
            'amount'                => $amount,
            'relatedTransactionId'  => $trans_to_void_data[Payment::TRANSACTION_ID],
            'authCode'              => $auth_code,
            'comment'               => '',
            'merchant_unique_id'    => $order->getIncrementId(),
            'urlDetails'            => [
                'notificationUrl' => $this->config->getCallbackDmnUrl(
                    $order->getIncrementId(),
                    $order->getStoreId(),
                    ['invoice_id' => $inv_id]
                ),
            ],
        ];

        $params = array_merge_recursive($params, parent::getParams());

//        $this->logger->updateRequest(
//            $this->getRequestId(),
//            ['increment_id' => $order->getIncrementId()]
//        );

        return $params;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getChecksumKeys()
    {
        return [
            'merchantId',
            'merchantSiteId',
            'clientRequestId',
            'clientUniqueId',
            'amount',
            'currency',
            'relatedTransactionId',
            'authCode',
            'comment',
            'urlDetails',
            'timeStamp',
        ];
    }
}
