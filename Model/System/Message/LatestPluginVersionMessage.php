<?php

namespace Nuvei\Payments\Model\System\Message;

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
    
    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $directory,
        \Nuvei\Payments\Model\Config $modulConfig
    ) {
        $this->directory    = $directory;
        $this->modulConfig  = $modulConfig;
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
        $file = $this->directory->getPath('tmp') . DIRECTORY_SEPARATOR . 'nuvei-plugin-latest-version.txt';
        
        if (!file_exists($file)) {
            $this->modulConfig->createLog('LatestPluginVersionMessage - version file does not exists.');
            return false;
        }
        
        if (!is_readable($file)) {
            $this->modulConfig->createLog('LatestPluginVersionMessage Error - '
                . 'version file exists, but is not readable!');
            return false;
        }
        
        $git_version = (int) str_replace('.', '', trim(file_get_contents($file)));
        
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
        return __('There is a new version of Nuvei Plugin available. <a href="https://github.'
            . 'com/SafeChargeInternational/safecharge_magento_v2/blob/master/CHANGELOG.md" '
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
