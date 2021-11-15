<?php

namespace Nuvei\Payments\Block\System\Config;

class GetNotifyUrl implements \Magento\Config\Model\Config\CommentInterface
{
    private $config;
    
    public function __construct(\Nuvei\Payments\Model\Config $config)
    {
        $this->config = $config;
    }

    public function getCommentText($elementValue)  //the method has to be named getCommentText
    {
        //do some calculations here
        $url = $this->config->getCallbackDmnUrl();
        $url_parts = explode('order', $url);
        
        return $url_parts[0];
    }
}
