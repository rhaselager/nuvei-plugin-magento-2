<?php

namespace Nuvei\Checkout\Model\Request;

use Nuvei\Checkout\Model\AbstractRequest;
use Nuvei\Checkout\Model\RequestInterface;

class GetSessionToken extends AbstractRequest implements RequestInterface
{
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
