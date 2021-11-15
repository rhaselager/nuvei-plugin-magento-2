<?php

namespace Nuvei\Payments\Model\Request;

use Magento\Checkout\Model\Session as CheckoutSession;
use Nuvei\Payments\Lib\Http\Client\Curl;
use Nuvei\Payments\Model\AbstractRequest;
use Nuvei\Payments\Model\AbstractResponse;
use Nuvei\Payments\Model\Config;
use Nuvei\Payments\Model\Payment;
use Nuvei\Payments\Model\Request\Factory as RequestFactory;
use Nuvei\Payments\Model\RequestInterface;
use Nuvei\Payments\Model\Response\Factory as ResponseFactory;

/**
 * Nuvei Payments paymentAPM request model.
 */
class PaymentApm extends AbstractRequest implements RequestInterface
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    
    private $paymentMethod;
    private $paymentMethodFields;
    private $savePaymentMethod;

    /**
     * @param Config           $config
     * @param Curl             $curl
     * @param ResponseFactory  $responseFactory
     * @param Factory          $requestFactory
     * @param CheckoutSession  $checkoutSession
     */
    public function __construct(
        \Nuvei\Payments\Model\Logger $logger,
        Config $config,
        Curl $curl,
        ResponseFactory $responseFactory,
        RequestFactory $requestFactory,
        CheckoutSession $checkoutSession
    ) {
        parent::__construct(
            $logger,
            $config,
            $curl,
            $responseFactory
        );

        $this->requestFactory   = $requestFactory;
        $this->checkoutSession  = $checkoutSession;
    }
    
    public function process()
    {
        $resp = $this->sendRequest(true);
        
        $transactionStatus = '';
        $return = [
            'status' => $resp['status']
        ];
        
        $this->config->createLog($resp);
        
        if (!empty($resp['transactionStatus'])) {
            $transactionStatus = (string) $resp['transactionStatus'];
        }
        
        if (!empty($resp['redirectURL'])) {
            $return['redirectUrl'] = (string) $resp['redirectURL'];
        } elseif (!empty($resp['paymentOption']['redirectUrl'])) {
            $return['redirectUrl'] = (string) $resp['paymentOption']['redirectUrl'];
        } else {
            switch ($transactionStatus) {
                case 'APPROVED':
                    $return['redirectUrl'] = $this->config->getCallbackSuccessUrl();
                    break;
                
                case 'PENDING':
                    $return['redirectUrl'] = $this->config->getCallbackPendingUrl();
                    break;
                
                case 'DECLINED':
                case 'ERROR':
                default:
                    $return['redirectUrl'] = $this->config->getCallbackErrorUrl();
                    break;
            }
        }
        
        return $return;
    }

    /**
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = trim((string)$paymentMethod);
        return $this;
    }
    
    /**
     * @param array $paymentMethodFields
     * @return $this
     */
    public function setPaymentMethodFields($paymentMethodFields)
    {
        $this->paymentMethodFields = $paymentMethodFields;
        return $this;
    }
    
    public function setSavePaymentMethod($savePaymentMethod)
    {
        $this->savePaymentMethod = $savePaymentMethod;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return is_numeric($this->paymentMethod) ? self::PAYMENT_UPO_APM_METHOD : self::PAYMENT_APM_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractResponse::PAYMENT_APM_HANDLER;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getParams()
    {
        $quote            = $this->checkoutSession->getQuote();
        $quotePayment    = $quote->getPayment();

        $this->config->createLog(
            $quotePayment->getAdditionalInformation(Payment::TRANSACTION_ORDER_ID),
            'PaymentAPM TRANSACTION_ORDER_ID'
        );
        
        $reservedOrderId = $quotePayment->getAdditionalInformation(Payment::TRANSACTION_ORDER_ID)
            ?: $this->config->getReservedOrderId();
        
        $order_data            = $quotePayment->getAdditionalInformation(Payment::CREATE_ORDER_DATA);
        $billing_address    = $this->config->getQuoteBillingAddress();
        
        if (empty($order_data) || empty($order_data['sessionToken'])) {
            $msg = 'PaymentApm Error - missing Session Token.';
            
            $this->config->createLog($order_data, $msg);
            
            throw new PaymentException(__($msg));
        }
        
        $this->config->createLog($order_data, 'PaymentAPM $order_data');
        
        $params = array_merge_recursive(
            $this->getQuoteData($quote),
            [
                'sessionToken'          => $order_data['sessionToken'],
                'amount'                => (float)$quote->getGrandTotal(),
                
                'urlDetails'            => [
                    'successUrl'        => $this->config->getCallbackSuccessUrl(),
                    'failureUrl'        => $this->config->getCallbackErrorUrl(),
                    'pendingUrl'        => $this->config->getCallbackPendingUrl(),
                    'backUrl'            => $this->config->getBackUrl(),
                    'notificationUrl'    => $this->config->getCallbackDmnUrl($reservedOrderId),
                ],
                
                'paymentMethod'            => $this->paymentMethod,
            ]
        );
        
        $params['clientUniqueId'] = $this->config->setClientUniqueId($reservedOrderId);
        
        // UPO APM
        if (is_numeric($this->paymentMethod)) {
            $params['paymentOption']['userPaymentOptionId'] = $this->paymentMethod;
            $params['userTokenId'] = $billing_address['email'];
        } elseif (!empty($this->paymentMethodFields)) {
            $params['userAccountDetails'] = $this->paymentMethodFields;
        }
        
        // APM
        if ((int) $this->savePaymentMethod === 1) {
            $params['userTokenId'] = $billing_address['email'];
        }

        $params = array_merge_recursive($params, parent::getParams());

//        $this->logger->updateRequest(
//            $this->getRequestId(),
//            [
//                'parent_request_id' => $quotePayment->getAdditionalInformation(Payment::TRANSACTION_REQUEST_ID),
//                'increment_id' => $this->config->getReservedOrderId(),
//            ]
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
            'amount',
            'currency',
            'timeStamp',
        ];
    }
}
