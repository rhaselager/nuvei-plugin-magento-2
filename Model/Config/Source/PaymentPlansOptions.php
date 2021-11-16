<?php

namespace Nuvei\Checkout\Model\Config\Source;

class PaymentPlansOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
//    protected $eavConfig;
    
    private $directory;
    private $config;
    private $file;
    private $driverManager;
    
    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $directory,
        \Nuvei\Checkout\Model\Config $config,
        \Magento\Framework\Filesystem\Io\File $file
    ) {
        $this->directory        = $directory;
        $this->config           = $config;
        $this->file             = $file;
    }
    
    public function getAllOptions()
    {
        $this->_options[] = [
            'label' => __('No Plan'),
            'value' => 1 // need to be greater than 0

        ];
        
        # json version
        $file_name = $this->directory->getPath('tmp') . DIRECTORY_SEPARATOR
            . \Nuvei\Checkout\Model\Config::PAYMENT_PLANS_FILE_NAME;
        
        if (is_readable($file_name)) {
            try {
                $cont = json_decode($this->file->read($file_name), true);

                if (!empty($cont['plans']) && is_array($cont['plans'])) {
                    foreach ($cont['plans'] as $data) {
                        $this->_options[] = [
                            'label' => $data['name'],
                            'value' => $data['planId']

                        ];
                    }
                }
            } catch (Exception $e) {
                $this->config->createLog($e->getMessage(), 'PaymentPlansOptions Exception');
            }
        } elseif ($this->file->fileExists($file_name)) {
            $this->config->createLog('PaymentPlansOptions Error - ' . $file_name . ' exists, but is not readable.');
        } else {
            $this->config->createLog('PaymentPlansOption - ' . $file_name . ' does not exists.');
        }
        # json version END

        return $this->_options;
    }
}
