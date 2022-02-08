<?php

namespace Nuvei\Checkout\Model\System\Message;

/**
 * Show System message if there is new version of the plugin,
 *
 * @author Nuvei
 */
class LatestPluginVersionMessage implements \Magento\Framework\Notification\MessageInterface
{
    const MESSAGE_IDENTITY = 'nuvei_plugin_version_message';
    
    private $directory;
    private $modulConfig;
    private $fileSystem;
    
    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $directory,
        \Nuvei\Checkout\Model\Config $modulConfig,
        \Magento\Framework\Filesystem\DriverInterface $fileSystem
    ) {
        $this->directory    = $directory;
        $this->modulConfig  = $modulConfig;
        $this->fileSystem   = $fileSystem;
    }

    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;
    }
    
    /**
     * Check whether the system message should be shown
     *
     * @return bool
     */
    public function isDisplayed()
    {
        if ($this->moduleConfig->isActive() === false) {
            $this->moduleConfig->createLog('LatestPluginVersionMessage Error - the module is not active.');
            return;
        }
        
        try {
            $path = $this->directory->getPath('tmp');
            
            // check git for version on every 7th day
//            if( (int) date('d', time()) % 7 == 0 ) {
//                $this->curl->get('https://raw.githubusercontent.com/SafeChargeInternational/'
//                    . 'safecharge_magento_v2/master/composer.json');
//                $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
//                $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
//
//                $result = $this->curl->getBody();
//                $array  = json_decode($result, true);
//
//                if (empty($array['version'])) {
//                    $this->moduleConfig->createLog($result, 'LatestPluginVersionMessage Error - missing version.');
//                    return;
//                }
//
//                $res = $this->fileSystem->filePutContents(
//                    $path . DIRECTORY_SEPARATOR . 'nuvei-plugin-latest-version.txt',
//                    $array['version']
//                );
//
//                if (!$res) {
//                    $this->moduleConfig->createLog('LatestPluginVersionMessage Error - file was not created.');
//                }
//            }
        } catch (Exception $ex) {
            $this->moduleConfig->createLog($ex->getMessage(), 'LatestPluginVersionMessage Exception:');
        }
        
        $file = $path . DIRECTORY_SEPARATOR . 'nuvei-plugin-latest-version.txt';
        
        if (!$this->fileSystem->isFile($file)) {
            $this->modulConfig->createLog('LatestPluginVersionMessage - version file does not exists.');
            return false;
        }
        
        if (!$this->fileSystem->isReadable($file)) {
            $this->modulConfig->createLog('LatestPluginVersionMessage Error - '
                . 'version file exists, but is not readable!');
            return false;
        }
        
        $git_version = (int) str_replace('.', '', trim($this->fileSystem->fileGetContents($file)));
        
        $this_version = str_replace('Magento Plugin ', '', $this->modulConfig->getSourcePlatformField());
        $this_version = (int) str_replace('.', '', $this_version);
        
        if ($git_version > $this_version) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Retrieve system message text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        return __('There is a new version of Nuvei Plugin available. '
            . '<a href="https://github.com/SafeChargeInternational/safecharge_magento_v2/blob/master/CHANGELOG.md" '
            . 'target="_blank">View version details.</a>');
    }
    
    /**
     * Retrieve system message severity
     * Possible default system message types:
     * - MessageInterface::SEVERITY_CRITICAL
     * - MessageInterface::SEVERITY_MAJOR
     * - MessageInterface::SEVERITY_MINOR
     * - MessageInterface::SEVERITY_NOTICE
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_NOTICE;
    }
}
