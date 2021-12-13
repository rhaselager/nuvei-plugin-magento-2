<?php

namespace Nuvei\Checkout\Cron;

class CheckForLatestVersion
{
    private $moduleConfig;
    private $directory;
    private $fileSystem;
    private $curl;

    public function __construct(
        \Nuvei\Checkout\Model\Config $moduleConfig,
        \Magento\Framework\Filesystem\DirectoryList $directory,
        \Magento\Framework\Filesystem\DriverInterface $fileSystem,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->directory    = $directory;
        $this->curl         = $curl;
    }

    public function execute()
    {
        if ($this->moduleConfig->isActive() === false) {
            $this->moduleConfig->createLog('CheckForLatestVersion Error - the module is not active.');
            return;
        }
        
        $this->moduleConfig->createLog('CheckForLatestVersion Cron');
        
        try {
            $this->curl->get('https://raw.githubusercontent.com/SafeChargeInternational/'
                . 'safecharge_magento_v2/master/composer.json');
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            
            $result = $this->curl->getBody();
            
//            $ch = curl_init();
//
//            curl_setopt(
//                $ch,
//                CURLOPT_URL,
//                'https://raw.githubusercontent.com/SafeChargeInternational/safecharge_magento_v2/master/composer.json'
//            );
//
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//
//            $data = curl_exec($ch);
//            curl_close($ch);

//            $array = json_decode($data, true);
            $array = json_decode($result, true);
            
            if (empty($array['version'])) {
//                $this->moduleConfig->createLog($data, 'CheckForLatestVersion Error - missing version.');
                $this->moduleConfig->createLog($result, 'CheckForLatestVersion Error - missing version.');
                return;
            }
        
            $path = $this->directory->getPath('tmp');
            
            $res = $this->fileSystem->filePutContents(
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
