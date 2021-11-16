<?php

namespace Nuvei\Checkout\Block\System\Config;

class ShowLastDownloadTime implements \Magento\Config\Model\Config\CommentInterface
{
    private $config;
    private $directory;
    
    public function __construct(
        \Nuvei\Checkout\Model\Config $config,
        \Magento\Framework\Filesystem\DirectoryList $directory
    ) {
        $this->config       = $config;
        $this->directory    = $directory;
    }

    public function getCommentText($elementValue)
    {
        $text = '';
        $file = $this->directory->getPath('tmp') . DIRECTORY_SEPARATOR
            . $this->config::PAYMENT_PLANS_FILE_NAME;
        
        if (file_exists($file)) {
            $text = __('Last download: ') . date('Y-m-d H:i:s', filectime($file));
        }
        
        return $text;
    }
}
