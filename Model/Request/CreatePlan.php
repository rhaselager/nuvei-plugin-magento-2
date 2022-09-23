<?php

namespace Nuvei\Checkout\Model\Request;

use Nuvei\Checkout\Model\AbstractRequest;
use Nuvei\Checkout\Model\RequestInterface;

class CreatePlan extends AbstractRequest implements RequestInterface
{
//    protected $requestFactory;
//    protected $config;
    protected $storeManager;
    
    public function __construct(
        //        \Nuvei\Checkout\Model\Logger $logger,
        \Nuvei\Checkout\Model\Config $config,
        \Nuvei\Checkout\Lib\Http\Client\Curl $curl,
        \Nuvei\Checkout\Model\Response\Factory $responseFactory,
        //        \Nuvei\Checkout\Model\Request\Factory $requestFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
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
        $this->storeManager     = $storeManager;
    }
    
    public function process()
    {
        return $this->sendRequest(true);
    }
    
    protected function getRequestMethod()
    {
        return self::CREATE_MERCHANT_PAYMENT_PLAN;
    }
    
    protected function getResponseHandlerType()
    {
        return '';
    }
    
    protected function getParams()
    {
        $params = array_merge_recursive(
            [
                'name'              => 'Default_plan_for_site_' . $this->config->getMerchantSiteId(),
                'initialAmount'     => 0,
                'recurringAmount'   => 1,
                'currency'          => $this->storeManager->getStore()->getBaseCurrencyCode(),
                'startAfter'        => [
                                        'day'   => 0,
                                        'month' => 1,
                                        'year'  => 0,
                                    ],
                'recurringPeriod'   => [
                                        'day'   => 0,
                                        'month' => 1,
                                        'year'  => 0,
                                    ],
                'endAfter'          => [
                                        'day'   => 0,
                                        'month' => 0,
                                        'year'  => 1,
                                    ],
                'planStatus'        => 'ACTIVE'
            ],
            parent::getParams()
        );
        
        return $params;
    }
    
    protected function getChecksumKeys()
    {
        return [
            'merchantId',
            'merchantSiteId',
            'name',
            'initialAmount',
            'recurringAmount',
            'currency',
            'timeStamp',
        ];
    }
}
