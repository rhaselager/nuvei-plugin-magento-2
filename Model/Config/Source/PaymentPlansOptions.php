<?php

namespace Nuvei\Checkout\Model\Config\Source;

class PaymentPlansOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
//    protected $eavConfig;
    
    private $directory;
//    private $file;
    private $driverManager;
//    private $fileSystem;
    private $readerWriter;
    
    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $directory,
//        \Magento\Framework\Filesystem\Io\File $file,
//        \Magento\Framework\Filesystem\DriverInterface $fileSystem,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter
    ) {
        $this->directory    = $directory;
//        $this->file         = $file;
//        $this->fileSystem   = $fileSystem;
        $this->readerWriter = $readerWriter;
    }
    
    public function getAllOptions()
    {
        $this->_options[] = [
            'label' => __('No Plan'),
            'value' => 1 // need to be greater than 0

        ];
        
        # json version
        $file_name = $this->directory->getPath('log') . DIRECTORY_SEPARATOR
            . \Nuvei\Checkout\Model\Config::PAYMENT_PLANS_FILE_NAME;
        
        $cont = json_decode($this->readerWriter->readFile($file_name), true);
        
        if(is_array($cont) && !empty($cont['plans']) && is_array($cont['plans'])) {
//        if ($this->fileSystem->isReadable($file_name)) {
//            try {
//                $cont = json_decode($this->file->read($file_name), true);
//
//                if (!empty($cont['plans']) && is_array($cont['plans'])) {
                    foreach ($cont['plans'] as $data) {
                        $this->_options[] = [
                            'label' => $data['name'],
                            'value' => $data['planId']

                        ];
                    }
//                }
//            } catch (Exception $e) {
//                $this->readerWriter->createLog($e->getMessage(), 'PaymentPlansOptions Exception');
//            }
//        } elseif ($this->file->fileExists($file_name)) {
//            $this->readerWriter->createLog('PaymentPlansOptions Error - ' . $file_name . ' exists, but is not readable.');
//        } else {
//            $this->readerWriter->createLog('PaymentPlansOption - ' . $file_name . ' does not exists.');
        } else {
            $this->readerWriter->createLog('PaymentPlansOption Error - problem when try to extract plans from ' . $file_name);
        }
        # json version END

        return $this->_options;
    }
}

