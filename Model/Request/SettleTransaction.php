<?php

namespace Nuvei\Checkout\Model\Request;

use Nuvei\Checkout\Model\AbstractRequest;
use Nuvei\Checkout\Model\AbstractResponse;
use Nuvei\Checkout\Model\RequestInterface;
use Nuvei\Checkout\Model\Payment;
use Magento\Framework\Exception\PaymentException;

class SettleTransaction extends AbstractRequest implements RequestInterface
{
    protected $config;
    protected $payment;
    
    private $invoice_id;
    
    /**
     * @param Logger           $logger
     * @param Config           $config
     * @param Curl             $curl
     * @param ResponseFactory  $responseFactory
     */
    public function __construct(
        \Nuvei\Checkout\Model\Logger $logger,
        \Nuvei\Checkout\Model\Config $config,
        \Nuvei\Checkout\Lib\Http\Client\Curl $curl,
        \Nuvei\Checkout\Model\Response\Factory $responseFactory
    ) {
        parent::__construct(
            $logger,
            $config,
            $curl,
            $responseFactory
        );
    }
    
    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws PaymentException
     */
    public function process()
    {
        $resp = $this->sendRequest(true);
        
        return $resp;
    }
    
    public function setInvoiceId($invoice_id)
    {
        $this->invoice_id = $invoice_id;
        
        return $this;
    }
    
    public function setPayment($payment)
    {
        $this->payment = $payment;
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getParams()
    {
        $order                  = $this->payment->getOrder();
        $order_total            = round((float) $order->getBaseGrandTotal(), 2);
        $ord_trans_addit_info   = $this->payment->getAdditionalInformation(Payment::ORDER_TRANSACTIONS_DATA);
        $trans_to_settle        = [];
        
        $this->config->createLog($ord_trans_addit_info, 'getParams');
        
        if (!empty($ord_trans_addit_info) && is_array($ord_trans_addit_info)) {
            foreach (array_reverse($ord_trans_addit_info) as $trans) {
                if (strtolower($trans[Payment::TRANSACTION_STATUS]) == 'approved'
                    && strtolower($trans[Payment::TRANSACTION_TYPE]) == 'auth'
                ) {
                    $trans_to_settle = $trans;
                    break;
                }
            }
        }
        
        if (empty($trans_to_settle[Payment::TRANSACTION_AUTH_CODE])
            || empty($trans_to_settle[Payment::TRANSACTION_ID])
        ) {
            $msg = 'Settle Error - Missing Auth paramters.';
            
            $this->config->createLog($trans_to_settle, $msg);
            
            throw new PaymentException(__($msg));
        }
        
        $getIncrementId = $order->getIncrementId();

        $params = array_merge_recursive(
            parent::getParams(),
            [
                'clientUniqueId'            => $getIncrementId,
                'amount'                    => $order_total,
                'currency'                  => $order->getBaseCurrencyCode(),
                'relatedTransactionId'      => $trans_to_settle[Payment::TRANSACTION_ID],
                'authCode'                  => $trans_to_settle[Payment::TRANSACTION_AUTH_CODE],
                'urlDetails'                => [
                    'notificationUrl' => $this->config
                        ->getCallbackDmnUrl($getIncrementId, null, ['invoice_id' => $this->invoice_id]),
                ],
            ]
        );
                
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
            'urlDetails',
            'timeStamp',
        ];
    }
    
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return self::PAYMENT_SETTLE_METHOD;
    }
    
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return '';
    }
}
