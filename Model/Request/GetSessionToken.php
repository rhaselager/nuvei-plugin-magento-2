<?php

namespace Nuvei\Checkout\Model\Request;

use Nuvei\Checkout\Model\AbstractRequest;
use Nuvei\Checkout\Model\RequestInterface;

class GetSessionToken extends AbstractRequest implements RequestInterface
{
    protected $requestFactory;
    protected $config;
    
    public function __construct(
        \Nuvei\Checkout\Model\Logger $logger,
        \Nuvei\Checkout\Model\Config $config,
        \Nuvei\Checkout\Lib\Http\Client\Curl $curl,
        \Nuvei\Checkout\Model\Response\Factory $responseFactory,
        \Nuvei\Checkout\Model\Request\Factory $requestFactory
    ) {
        parent::__construct(
            $logger,
            $config,
            $curl,
            $responseFactory
        );

        $this->requestFactory   = $requestFactory;
        $this->config           = $config;
    }
    
    public function process()
    {
        return $this->sendRequest(true);
    }
    
    protected function getRequestMethod()
    {
        return self::GET_SESSION_TOKEN;
    }
    
    protected function getResponseHandlerType()
    {
        return '';
    }
    
    protected function getParams()
    {
        return parent::getParams();
    }
    
    protected function getChecksumKeys()
    {
        return [
            'merchantId',
            'merchantSiteId',
            'clientRequestId',
            'timeStamp',
        ];
    }
    
}
