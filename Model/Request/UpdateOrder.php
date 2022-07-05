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

/**
 * Nuvei Checkout open order request model.
 */
class UpdateOrder extends AbstractRequest implements RequestInterface
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var array
     */
    protected $orderData;
    
    private $cart;
    
    /**
     * @param Config           $config
     * @param Curl             $curl
     * @param ResponseFactory  $responseFactory
     * @param Factory          $requestFactory
     */
    public function __construct(
//        \Nuvei\Checkout\Model\Logger $logger,
        Config $config,
        Curl $curl,
        ResponseFactory $responseFactory,
//        RequestFactory $requestFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter
    ) {
        parent::__construct(
//            $logger,
            $config,
            $curl,
            $responseFactory,
            $readerWriter
        );

//        $this->requestFactory   = $requestFactory;
        $this->cart             = $cart;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return self::UPDATE_ORDER_METHOD;
    }

    /**
     * @param array $orderData
     *
     * @return OpenOrder
     */
    public function setOrderData(array $orderData)
    {
        $this->orderData = $orderData;
        return $this;
    }
    
    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws PaymentException
     */
    public function process()
    {
        $req_resp = $this->sendRequest(true, true);
        
        return $req_resp;
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
            $this->config->createLog('UpdateOrder Error - There is no Cart data.');
            
            throw new PaymentException(__('There is no Cart data.'));
        }
        
        // iterate over Items and search for Subscriptions
        $items_data = $this->config->getProductPlanData();
        
        $this->config->setNuveiUseCcOnly(!empty($items_data['subs_data']) ? true : false);
        
        $billing_address    = $this->config->getQuoteBillingAddress();
        $amount             = $this->config->getQuoteBaseTotal();
        
        $params = array_merge_recursive(
            parent::getParams(),
            [
                'currency'          => $this->config->getQuoteBaseCurrency(),
                'amount'            => $amount,
                'billingAddress'    => $billing_address,
                'shippingAddress'   => $this->config->getQuoteShippingAddress(),
                
                'items'             => [[
                    'name'      => 'magento_order',
                    'price'     => $amount,
                    'quantity'  => 1,
                ]],
                
                'merchantDetails'   => [
                    // pass amount
                    'customField1'  => $amount,
                    // subscription data
                    'customField2'  => isset($items_data['subs_data'])
                        ? json_encode($items_data['subs_data']) : '',
                    # customField3 is passed in AbstractRequest
                    // time when we create the request
                    'customField4'  => time(),
                    // list of Order items
                    'customField5'  => isset($items_data['items_data'])
                        ? json_encode($items_data['items_data']) : '',
                ],
            ]
        );
        
        $params['userDetails']      = $params['billingAddress'];
        $params['sessionToken']     = $this->orderData['sessionToken'];
        $params['orderId']          = isset($this->orderData['orderId']) ? $this->orderData['orderId'] : '';
        $params['clientRequestId']  = isset($this->orderData['clientRequestId'])
            ? $this->orderData['clientRequestId'] : '';
        
        // for rebilling
        if (!empty($this->config->getProductPlanData())) {
            $params['isRebilling'] = 0;
            $params['paymentOption']['card']['threeD']['rebillFrequency']   = 1;
            $params['paymentOption']['card']['threeD']['rebillExpiry']
                = date('Ymd', strtotime("+10 years"));
        } else { // for normal transaction
            $params['isRebilling'] = 1;
            $params['paymentOption']['card']['threeD']['rebillExpiry']      = date('Ymd', time());
            $params['paymentOption']['card']['threeD']['rebillFrequency']   = 0;
        }
        
        $params['checksum'] = hash(
            $this->config->getHash(),
            $this->config->getMerchantId() . $this->config->getMerchantSiteId() . $params['clientRequestId']
                . $params['amount'] . $params['currency'] . $params['timeStamp'] . $this->config->getMerchantSecretKey()
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
        } catch (Exception $e) {
            $this->config->createLog($e->getMessage(), 'getOptions() Exception');
        }

        return $return;
    }
}
