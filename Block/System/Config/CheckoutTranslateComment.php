<?php

namespace Nuvei\Checkout\Block\System\Config;

class CheckoutTranslateComment implements \Magento\Config\Model\Config\CommentInterface
{
    private $config;
    
    public function __construct(\Nuvei\Checkout\Model\Config $config)
    {
        $this->config = $config;
    }

    public function getCommentText($elementValue)  //the method has to be named getCommentText
    {
        return __('Set your translations like in the example:') . '<br/>'
            . '<code>{</br>'
                . '&nbsp;&nbsp;&nbsp;"de": {</br>'
                    . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"doNotHonor": "you dont have enough money",'
                    . '</br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"DECLINE": "declined"'
                . '</br>&nbsp;&nbsp;&nbsp;},'
            . '</br>}</code>'
            . '</br>'
            . __('For more information, please check the <a href="'
                . 'https://docs.nuvei.com/documentation/accept-payment/web-sdk/'
                . 'advanced-styling/#i18n-styling" target="_blank">Documentation</a>.')
            
            ;
    }
}
