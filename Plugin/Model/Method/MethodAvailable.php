<?php

namespace Nuvei\Payments\Plugin\Model\Method;

use Nuvei\Payments\Model\Config;
use Nuvei\Payments\Model\Payment;

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
