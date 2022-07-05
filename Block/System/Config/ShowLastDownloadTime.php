<?php

namespace Nuvei\Checkout\Block\System\Config;

class ShowLastDownloadTime implements \Magento\Config\Model\Config\CommentInterface
{
    private $config;
    private $directory;
    private $file;
    
    public function __construct(
        \Nuvei\Checkout\Model\Config $config,
        \Magento\Framework\Filesystem\DirectoryList $directory,
        \Magento\Framework\Filesystem\Driver\File $file
    ) {
        $this->config       = $config;
        $this->directory    = $directory;
        $this->file         = $file;
    }

    public function getCommentText($elementValue)
    {
        $text = '';
        $file = $this->directory->getPath('log') . DIRECTORY_SEPARATOR
            . $this->config::PAYMENT_PLANS_FILE_NAME;
        
        if ($this->file->isExists($file)) {
            $fileData   = $this->file->stat($file);
            $text       = __('Last download: ') . date('Y-m-d H:i:s', $fileData['ctime']);
//            $text = __('Last download: ') . date('Y-m-d H:i:s', filectime($file));
        }
        
        return $text;
    }
}
