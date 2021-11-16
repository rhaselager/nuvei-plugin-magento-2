<?php

namespace Nuvei\Checkout\Cron;

class CheckForLatestVersion
{
    private $moduleConfig;
    private $directory;

    public function __construct(
        \Nuvei\Checkout\Model\Config $moduleConfig,
        \Magento\Framework\Filesystem\DirectoryList $directory
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->directory    = $directory;
    }

    public function execute()
    {
        if ($this->moduleConfig->isActive() === false) {
            $this->moduleConfig->createLog('CheckForLatestVersion Error - the module is not active.');
            return;
        }
        
        $this->moduleConfig->createLog('CheckForLatestVersion Cron');
        
        try {
            $ch = curl_init();

            curl_setopt(
                $ch,
                CURLOPT_URL,
                'https://raw.githubusercontent.com/SafeChargeInternational/safecharge_magento_v2/master/composer.json'
            );

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $data = curl_exec($ch);
            curl_close($ch);

            $array = json_decode($data, true);
            
            if (empty($array['version'])) {
                $this->moduleConfig->createLog($data, 'CheckForLatestVersion Error - missing version.');
                return;
            }
        
            $path = $this->directory->getPath('tmp');
            
            $res = file_put_contents(
                $path . DIRECTORY_SEPARATOR . 'nuvei-plugin-latest-version.txt',
                $array['version']
            );
            
            if (!$res) {
                $this->moduleConfig->createLog('CheckForLatestVersion Error - file was not created.');
            }
        } catch (Exception $ex) {
            $this->moduleConfig->createLog($ex->getMessage(), 'CheckForLatestVersion Exception:');
        }
    }
}
