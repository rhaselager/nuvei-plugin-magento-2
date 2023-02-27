<?php

namespace Nuvei\Checkout\Model\Request;

use Nuvei\Checkout\Lib\Http\Client\Curl;
use Nuvei\Checkout\Model\AbstractRequest;
use Nuvei\Checkout\Model\AbstractResponse;
use Nuvei\Checkout\Model\Config;
use Nuvei\Checkout\Model\RequestInterface;
use Nuvei\Checkout\Model\Response\Factory as ResponseFactory;
use Magento\Framework\Exception\PaymentException;
use Nuvei\Checkout\Model\Request\Factory as RequestFactory;
use Nuvei\Checkout\Model\Payment;

/**
 * Nuvei Checkout open order request model.
 */
class OpenOrder extends AbstractRequest implements RequestInterface
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var array
     */
    protected $orderData;
    
    protected $readerWriter;
    
    private $countryCode; // string
    private $quote;
    private $cart;
    private $items; // the products in the cart
    private $paymentsPlans;
    private $items_data     = [];
    private $subs_data      = [];
    private $requestParams  = [];
    private $is_rebilling   = false;

    /**
     * OpenOrder constructor.
     *
     * @param Logger $logger
     * @param Config            $config
     * @param Curl              $curl
     * @param ResponseFactory   $responseFactory
     * @param Factory           $requestFactory
     * @param Cart              $cart
     * @param ReaderWriter      $readerWriter
     * @param PaymentsPlans     $paymentsPlans
     */
    public function __construct(
        Config $config,
        Curl $curl,
        ResponseFactory $responseFactory,
        RequestFactory $requestFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter,
        \Nuvei\Checkout\Model\PaymentsPlans $paymentsPlans
    ) {
        parent::__construct(
            $config,
            $curl,
            $responseFactory,
            $readerWriter
        );

        $this->requestFactory   = $requestFactory;
        $this->cart             = $cart;
        $this->paymentsPlans    = $paymentsPlans;
        $this->readerWriter     = $readerWriter;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return self::OPEN_ORDER_METHOD;
    }

    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws PaymentException
     */
    public function process()
    {
        $this->quote    = $this->cart->getQuote();
        $this->items    = $this->quote->getItems();
        $order_data     = $this->quote->getPayment()->getAdditionalInformation(Payment::CREATE_ORDER_DATA);
        
        // iterate over Items and search for Subscriptions
        $this->items_data   = $this->paymentsPlans->getProductPlanData();
        $this->subs_data    = isset($this->items_data['subs_data']) ?: [];
        
        $this->readerWriter->createLog([
            '$this->subs_data'  => $this->subs_data,
            '$order_data'       => $order_data,
        ]);
        
        // will we call updateOrder?
        $callUpdateOrder = false;
        
        if (!empty($order_data)) {
            $callUpdateOrder = true;
        }
        
        if (empty($order_data['userTokenId']) && !empty($this->subs_data)) {
            $callUpdateOrder = false;
        }
        
        if ($callUpdateOrder) {
            $update_order_request = $this->requestFactory->create(AbstractRequest::UPDATE_ORDER_METHOD);

            $req_resp = $update_order_request
                ->setOrderData($order_data)
                ->process();
        }
        // /will we call updateOrder?
        
        // if UpdateOrder fails - continue with OpenOrder
        if (empty($req_resp['status']) || 'success' != strtolower($req_resp['status'])) {
            $req_resp = $this->sendRequest(true);
        }
        
        $this->orderId      = $req_resp['orderId'];
        $this->sessionToken = $req_resp['sessionToken'];
        $this->ooAmount     = $req_resp['merchantDetails']['customField1'];

        // save the session token in the Quote
        $add_info = [
            'sessionToken'      => $req_resp['sessionToken'],
            'clientRequestId'   => $req_resp['clientRequestId'],
            'orderId'           => $req_resp['orderId'],
        ];
        
        if (isset($req_resp['userTokenId'])) {
            $add_info['userTokenId'] = $req_resp['userTokenId'];
        }
        
        $this->quote->getPayment()->setAdditionalInformation(
            Payment::CREATE_ORDER_DATA,
            $add_info
        );
        $this->cart->getQuote()->save();
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractResponse::OPEN_ORDER_HANDLER;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws \Magento\Framework\Exception\PaymentException
     */
    protected function getParams()
    {
        if (null === $this->cart || empty($this->cart)) {
            $this->readerWriter->createLog('OpenOrder class Error - mising Cart data.');
            throw new PaymentException(__('There is no Cart data.'));
        }
        
        $this->config->setNuveiUseCcOnly(!empty($this->subs_data) ? true : false);
        
        $billing_address = $this->config->getQuoteBillingAddress();
        if (!empty($this->billingAddress)) {
            $billing_address['firstName']   = $this->billingAddress['firstname'] ?: $billing_address['firstName'];
            $billing_address['lastName']    = $this->billingAddress['lastname'] ?: $billing_address['lastName'];
            
            if (is_array($this->billingAddress['street']) && !empty($this->billingAddress['street'])) {
                $billing_address['address'] = implode(' ', $this->billingAddress['street']);
            }
            
            $billing_address['phone']   = $this->billingAddress['telephone'] ?: $billing_address['phone'];
            $billing_address['zip']     = $this->billingAddress['postcode'] ?: $billing_address['zip'];
            $billing_address['city']    = $this->billingAddress['city'] ?: $billing_address['city'];
            $billing_address['country'] = $this->billingAddress['countryId'] ?: $billing_address['country'];
        }
        
        $amount = $this->config->getQuoteBaseTotal();
        
        $params = [
            'clientUniqueId'    => $this->config->getCheckoutSession()->getQuoteId() . '_' . time(),
            'currency'          => $this->config->getQuoteBaseCurrency(),
            'amount'            => $amount,
            'deviceDetails'     => $this->config->getDeviceDetails(),
            'shippingAddress'   => $this->config->getQuoteShippingAddress(),
            'billingAddress'    => $billing_address,
            'transactionType'   => $this->config->getConfigValue('payment_action'),

            'urlDetails'        => [
                'successUrl'        => $this->config->getCallbackSuccessUrl(),
                'failureUrl'        => $this->config->getCallbackErrorUrl(),
                'pendingUrl'        => $this->config->getCallbackPendingUrl(),
                'backUrl'           => $this->config->getBackUrl(),
                'notificationUrl'   => $this->config->getCallbackDmnUrl(),
            ],

            'merchantDetails'    => [
                // pass amount
                'customField1' => $amount,
                // subscription data
                'customField2' => isset($this->subs_data)
                    ? json_encode($this->subs_data) : '',
                // customField3 is passed in AbstractRequest
                // time when we create the request
                'customField4' => time(),
                // list of Order items
                'customField5' => isset($this->items_data['items_data'])
                    ? json_encode($this->items_data['items_data']) : '',
            ],

//            'paymentOption'      => [
//                'card' => [
//                    'threeD' => [
//                        'isDynamic3D' => 1
//                    ]
//                ]
//            ],
        ];
        
        // show or not UPOs
        if ($this->config->canUseUpos()) {
            $params['userTokenId'] = $params['billingAddress']['email'];
        }
        
        // auto_close_popup
        if (1 == $this->config->getConfigValue('auto_close_popup')) {
            $params['urlDetails']['successUrl'] = $params['urlDetails']['pendingUrl']
                                                = $params['urlDetails']['failureUrl']
                                                = Config::NUVEI_SDK_AUTOCLOSE_URL;
        }
        
        $this->requestParams = array_merge_recursive(
            $params,
            parent::getParams()
        );
        
        // for rebilling
        if (!empty($this->subs_data)) {
            $this->requestParams['isRebilling'] = 0;
//            $this->requestParams['paymentOption']['card']['threeD']['rebillFrequency'] = 1;
//            $this->requestParams['paymentOption']['card']['threeD']['rebillExpiry']
//                = date('Ymd', strtotime("+10 years"));
            
            $this->requestParams['userTokenId'] = $params['billingAddress']['email'];
//            $this->config->getCheckoutSession()->setNuveiUserTokenId($this->requestParams['userTokenId']);
        }
            
        $this->requestParams['userDetails'] = $this->requestParams['billingAddress'];
        
        return $this->requestParams;
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
    
    /**
     * Get attribute options
     *
     * @param \Magento\Eav\Api\Data\AttributeInterface $attribute
     * @return array
     */
    private function getOptions(\Magento\Eav\Api\Data\AttributeInterface $attribute) : array
    {
        $return = [];

        try {
            $options = $attribute->getOptions();
            
            foreach ($options as $option) {
                if ($option->getValue()) {
                    $return[] = [
                        'value' => $option->getLabel(),
                        'label' => $option->getLabel(),
                        'parentAttributeLabel' => $attribute->getDefaultFrontendLabel()
                    ];
                }
            }
            
            return $return;
        } catch (\Exception $e) {
            $this->readerWriter->createLog($e->getMessage(), 'getOptions() Exception');
        }

        return $return;
    }
}
