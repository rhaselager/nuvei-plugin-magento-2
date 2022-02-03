<?php

namespace Nuvei\Checkout\Plugin\Model\Method;

use Nuvei\Checkout\Model\Config;
use Nuvei\Checkout\Model\Payment;

class MethodAvailable
{
    private $config;
    
    public function __construct(Config $config)
    {
        $this->config = $config;
    }
    
    public function afterGetAvailableMethods(\Magento\Payment\Model\MethodList $subject, $result)
    {
        $this->config->createLog('MethodAvailable afterGetAvailableMethods');
        
        if(!empty($this->config->getProductPlanData())) {
            foreach ($result as $key => $_result) {
                if ($_result->getCode() != Payment::METHOD_CODE) {
                    unset($result[$key]);
                }
            }
        }
        
        return $result;
    }
}
