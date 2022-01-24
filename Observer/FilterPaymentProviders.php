<?php

namespace Nuvei\Payments\Observer;

use Nuvei\Checout\Model\Config;
use Nuvei\Checout\Model\Payment;

/**
 * When in the cart we have a product with active Nuvei Payment Plan,
 * remove all other payment providers
 */
class FilterPaymentProviders implements \Magento\Framework\Event\ObserverInterface
{
    private $config;
    
    public function __construct(Config $config)
    {
        $this->config = $config;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $prod_plan_data = $this->config->getProductPlanData();
        
        if(!empty($prod_plan_data)
            && $observer->getEvent()->getMethodInstance()->getCode() != Payment::METHOD_CODE
        ){
            $checkResult = $observer->getEvent()->getResult();
            // this is disabling the payment method at checkout page
            $checkResult->setData('is_available', false);
        }
    }

}
