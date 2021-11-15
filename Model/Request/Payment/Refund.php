<?php

namespace Nuvei\Payments\Model\Request\Payment;

use Magento\Framework\Exception\PaymentException;
//use Magento\Sales\Model\Order\Payment\Transaction as OrderTransaction;
use Nuvei\Payments\Model\AbstractRequest;
use Nuvei\Payments\Model\AbstractResponse;
use Nuvei\Payments\Model\Payment;
use Nuvei\Payments\Model\Request\AbstractPayment;
use Nuvei\Payments\Model\RequestInterface;

/**
 * Nuvei Payments refund payment request model.
 */
class Refund extends AbstractPayment implements RequestInterface
{
    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;
    private $request;

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
     * @param float                             $amount
     */
    public function __construct(
        \Nuvei\Payments\Model\Logger $logger,
        \Nuvei\Payments\Model\Config $config,
        \Nuvei\Payments\Lib\Http\Client\Curl $curl,
        \Nuvei\Payments\Model\Request\Factory $requestFactory,
        \Nuvei\Payments\Model\Request\Payment\Factory $paymentRequestFactory,
        \Nuvei\Payments\Model\Response\Factory $responseFactory,
        \Magento\Sales\Model\Order\Payment $orderPayment,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Framework\App\Request\Http $request,
        $amount = 0.0
    ) {
        parent::__construct(
            $logger,
            $config,
            $curl,
            $requestFactory,
            $paymentRequestFactory,
            $responseFactory,
            $orderPayment,
            $amount
        );

        $this->transactionRepository    = $transactionRepository;
        $this->request                  = $request;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return AbstractRequest::PAYMENT_REFUND_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractResponse::PAYMENT_REFUND_HANDLER;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws \Magento\Framework\Exception\PaymentException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getParams()
    {
        $orderPayment           = $this->orderPayment;
        $ord_trans_addit_info   = $orderPayment->getAdditionalInformation(Payment::ORDER_TRANSACTIONS_DATA);
        $order                  = $orderPayment->getOrder();
        $trans_to_refund_data   = [];
        $inv_id                 = $this->request->getParam('invoice_id');
        
        if (!empty($ord_trans_addit_info) && is_array($ord_trans_addit_info)) {
            foreach (array_reverse($ord_trans_addit_info) as $trans) {
                if (strtolower($trans[Payment::TRANSACTION_STATUS]) == 'approved'
                    && in_array(strtolower($trans[Payment::TRANSACTION_TYPE]), ['sale', 'settle'])
                ) {
                    // sale
                    if (strtolower($trans[Payment::TRANSACTION_TYPE]) == 'sale') {
                        $trans_to_refund_data = $trans;
                        break;
                    } elseif (!empty($trans['invoice_id'])
                        && !empty($inv_id)
                        && $trans['invoice_id'] == $inv_id
                    ) { // settle
                        $trans_to_refund_data = $trans;
                        break;
                    }
                }
            }
        }
        
        if (empty($trans_to_refund_data[Payment::TRANSACTION_ID])) {
            $this->config->createLog(
                [
                    '$ord_trans_addit_info' => $ord_trans_addit_info,
                    '$trans_to_refund_data' =>$trans_to_refund_data
                ],
                'Refund Error - Transaction ID is empty.'
            );
            
            throw new PaymentException(__('Refund Error - Transaction ID is empty.'));
        }

        if (Payment::APM_METHOD_CC == $trans_to_refund_data[Payment::TRANSACTION_PAYMENT_METHOD]
            && empty($trans_to_refund_data[Payment::TRANSACTION_AUTH_CODE])
        ) {
            $msg = 'Refund Error - CC Transaction does not contain authorization code.';
            
            $this->config->createLog($trans_to_refund_data, $msg);
            
            throw new PaymentException(__($msg));
        }
        
        $auth_code = !empty($trans_to_refund_data[Payment::TRANSACTION_AUTH_CODE])
            ? $trans_to_refund_data[Payment::TRANSACTION_AUTH_CODE] : '';

        $params = [
            'clientUniqueId'        => $order->getIncrementId(),
            'currency'              => $order->getBaseCurrencyCode(),
            'amount'                => (float)$this->amount,
            'relatedTransactionId'  => $trans_to_refund_data[Payment::TRANSACTION_ID],
            'authCode'              => $auth_code,
            'comment'               => '',
            'merchant_unique_id'    => $order->getIncrementId(),
            'urlDetails'            => [
                'notificationUrl' => $this->config
                    ->getCallbackDmnUrl(
                        $order->getIncrementId(),
                        $order->getStoreId(),
                        ['invoice_id' => $inv_id]
                    ),
            ],
        ];

        $params = array_merge_recursive($params, parent::getParams());

//        $this->logger->updateRequest(
//            $this->getRequestId(),
//            ['increment_id' => $order->getIncrementId(),]
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
