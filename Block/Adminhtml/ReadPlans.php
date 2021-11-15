<?php

namespace Nuvei\Payments\Block\Adminhtml;

class ReadPlans extends \Magento\Backend\Block\Template
{
    protected $_template = 'Nuvei_Payments::readPlans.phtml';
    
    private $config;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Nuvei\Payments\Model\Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        
        $this->config = $config;
    }
    
    public function getPaymentPlans()
    {
        $file_name = $this->config->getTempPath() . DIRECTORY_SEPARATOR
            . \Nuvei\Payments\Model\Config::PAYMENT_PLANS_FILE_NAME;
        
        if (!is_readable($file_name)) {
            return '';
        }
        
        $file_cont = json_decode(file_get_contents($file_name), true);
        $plans = [];
        
        if (empty($file_cont['plans']) || !is_array($file_cont['plans'])) {
            return [];
        }
        
        foreach ($file_cont['plans'] as $data) {
            $plans[$data['planId']] = $data;
        }
        
        return json_encode($plans);
    }
}
