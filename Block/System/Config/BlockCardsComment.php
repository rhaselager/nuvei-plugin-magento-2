<?php

namespace Nuvei\Checkout\Block\System\Config;

class BlockCardsComment implements \Magento\Config\Model\Config\CommentInterface
{
    private $config;
    
    public function __construct(\Nuvei\Checkout\Model\Config $config)
    {
        $this->config = $config;
    }

    public function getCommentText($elementValue)  //the method has to be named getCommentText
    {
        return 'For examples <a href="https://docs.nuvei.com/documentation/accept-payment/checkout-2/payment-customization/#card-processing" target="_blank">check the Documentation</a>.';
    }
}
