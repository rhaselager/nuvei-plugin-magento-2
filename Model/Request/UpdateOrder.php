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
    
    protected $readerWriter;

    private $cart;
    private $paymentsPlans;
    
    /**
     * @param Config            $config
     * @param Curl              $curl
     * @param ResponseFactory   $responseFactory
     * @param Cart              $cart
     * @param ReaderWriter      $readerWriter
     * @param PaymentsPlans     $paymentsPlans
     */
    public function __construct(
        Config $config,
        Curl $curl,
        ResponseFactory $responseFactory,
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
            $this->readerWriter->createLog('UpdateOrder Error - There is no Cart data.');
            
            throw new PaymentException(__('There is no Cart data.'));
        }
        
        // iterate over Items and search for Subscriptions
        $items_data = $this->paymentsPlans->getProductPlanData();
        $subs_data  = isset($items_data['subs_data']) ? $items_data['subs_data'] : [];
        
        $this->config->setNuveiUseCcOnly(!empty($subs_data) ? true : false);
        
        $billing_address    = $this->config->getQuoteBillingAddress();
        $amount             = $this->config->getQuoteBaseTotal();
        
        $this->readerWriter->createLog($subs_data, 'update order - subs_data');
        
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
                    'customField2'  => json_encode($subs_data),
                    # customField3 is passed in AbstractRequest
                    // time when we create the request
                    'customField4'  => time(),
                    // list of Order items
                    'customField5'  => isset($items_data['items_data'])
                        ? json_encode($items_data['items_data']) : '',
                ],
            ]
        );
        
        // this is for the case when in the openOrder we passed amount = 0 and force the transactionType = Auth
        if ((float) $amount > 0) {
            $params['transactionType'] = $this->config->getConfigValue('payment_action');
        }
        
        $params['userDetails']      = $params['billingAddress'];
        $params['sessionToken']     = $this->orderData['sessionToken'];
        $params['orderId']          = isset($this->orderData['orderId']) ? $this->orderData['orderId'] : '';
        $params['clientRequestId']  = isset($this->orderData['clientRequestId'])
            ? $this->orderData['clientRequestId'] : '';
        
        $params['checksum'] = hash(
            $this->config->getConfigValue('hash'),
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
        } catch (\Exception $e) {
            $this->readerWriter->createLog($e->getMessage(), 'getOptions() Exception');
        }

        return $return;
    }
}
