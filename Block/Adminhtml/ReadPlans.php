<?php

namespace Nuvei\Checkout\Block\Adminhtml;

class ReadPlans extends \Magento\Backend\Block\Template
{
    protected $_template = 'Nuvei_Checkout::readPlans.phtml';
    
    private $config;
    private $fileSystem;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Nuvei\Checkout\Model\Config $config,
        \Magento\Framework\Filesystem\DriverInterface $fileSystem,
        array $data = []
    ) {
        parent::__construct($context, $data);
        
        $this->config       = $config;
        $this->fileSystem   = $fileSystem;
    }
    
    public function getPaymentPlans()
    {
        $file_name = $this->config->getTempPath() . DIRECTORY_SEPARATOR
            . \Nuvei\Checkout\Model\Config::PAYMENT_PLANS_FILE_NAME;
        
        if (!$this->fileSystem->isReadable($file_name)) {
            return '';
        }
        
        $file_cont = json_decode($this->fileSystem->fileGetContents($file_name), true);
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
