<?php

namespace Nuvei\Payments\Model\Request;

use Nuvei\Payments\Model\AbstractRequest;
use Nuvei\Payments\Model\RequestInterface;

class CreatePlan extends AbstractRequest implements RequestInterface
{
    protected $requestFactory;
    protected $config;
    protected $storeManager;
    
    public function __construct(
        \Nuvei\Payments\Model\Logger $logger,
        \Nuvei\Payments\Model\Config $config,
        \Nuvei\Payments\Lib\Http\Client\Curl $curl,
        \Nuvei\Payments\Model\Response\Factory $responseFactory,
        \Nuvei\Payments\Model\Request\Factory $requestFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct(
            $logger,
            $config,
            $curl,
            $responseFactory
        );

        $this->requestFactory   = $requestFactory;
        $this->config           = $config;
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
