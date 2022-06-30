<?php

namespace Nuvei\Checkout\Plugin\Model\Method;

use Nuvei\Checkout\Model\Config;
use Nuvei\Checkout\Model\Payment;

class MethodAvailable
{
    private $config;
    private $readerWriter;
    
    public function __construct(
        \Nuvei\Checkout\Model\Config $config,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter
    )
    {
        $this->config       = $config;
        $this->readerWriter = $readerWriter;
    }
    
    public function afterGetAvailableMethods(\Magento\Payment\Model\MethodList $subject, $result)
    {
        $this->readerWriter->createLog('MethodAvailable afterGetAvailableMethods');
        
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
