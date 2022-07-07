<?php

namespace Nuvei\Checkout\Model\Request\Payment;

use Magento\Framework\Exception\PaymentException;
use Nuvei\Checkout\Model\AbstractRequest;
use Nuvei\Checkout\Model\AbstractResponse;
use Nuvei\Checkout\Model\Payment;
use Nuvei\Checkout\Model\Request\AbstractPayment;
use Nuvei\Checkout\Model\RequestInterface;

/**
 * Nuvei Checkout settle payment request model.
 */
class Settle extends AbstractPayment implements RequestInterface
{
    protected $readerWriter;
    
    public function __construct(
        \Nuvei\Checkout\Model\Config $config, 
        \Nuvei\Checkout\Lib\Http\Client\Curl $curl, 
        \Nuvei\Checkout\Model\Response\Factory $responseFactory, 
        \Magento\Sales\Model\Order\Payment $orderPayment, 
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter
    ) {
        parent::__construct($config, $curl, $responseFactory, $orderPayment, $readerWriter);
        
        $this->readerWriter = $readerWriter;
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return AbstractRequest::PAYMENT_SETTLE_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractResponse::PAYMENT_SETTLE_HANDLER;
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
        $order                  = $orderPayment->getOrder();
        $ord_trans_addit_info    = $orderPayment->getAdditionalInformation(Payment::ORDER_TRANSACTIONS_DATA);
        $trans_to_settle        = [];
        
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
            
            $this->readerWriter->createLog($trans_to_settle, $msg);
            
            throw new PaymentException(__($msg));
        }
        
        $getIncrementId = $order->getIncrementId();

        $params = [
            'clientUniqueId'            => $getIncrementId,
            'amount'                    => $trans_to_settle['total_amount'],
            'currency'                  => $order->getBaseCurrencyCode(),
            'relatedTransactionId'      => $trans_to_settle[Payment::TRANSACTION_ID],
            'authCode'                  => $trans_to_settle[Payment::TRANSACTION_AUTH_CODE],
            'urlDetails'                => [
                'notificationUrl' => $this->config->getCallbackDmnUrl($getIncrementId),
            ],
        ];

        $params = array_merge_recursive(parent::getParams(), $params);

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
}
