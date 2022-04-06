<?php

namespace Nuvei\Checkout\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Nuvei Checkout config model.
 */
class Config
{
    const MODULE_NAME                           = 'Nuvei_Checkout';
    
    const PAYMENT_PLANS_ATTR_NAME               = 'nuvei_payment_plans';
    const PAYMENT_PLANS_ATTR_LABEL              = 'Nuvei Payment Plans';
    const PAYMENT_PLANS_FILE_NAME               = 'nuvei_payment_plans.json';
    
    const PAYMENT_SUBS_GROUP                    = 'Nuvei Subscription';
    
    const PAYMENT_SUBS_ENABLE_LABEL             = 'Enable Subscription';
    const PAYMENT_SUBS_ENABLE                   = 'nuvei_sub_enabled';
    
    const PAYMENT_SUBS_INTIT_AMOUNT_LABEL       = 'Initial Amount';
    const PAYMENT_SUBS_INTIT_AMOUNT             = 'nuvei_sub_init_amount';
    const PAYMENT_SUBS_REC_AMOUNT_LABEL         = 'Recurring Amount';
    const PAYMENT_SUBS_REC_AMOUNT               = 'nuvei_sub_rec_amount';
    
    const PAYMENT_SUBS_RECURR_UNITS             = 'nuvei_sub_recurr_units';
    const PAYMENT_SUBS_RECURR_UNITS_LABEL       = 'Recurring Units';
    const PAYMENT_SUBS_RECURR_PERIOD            = 'nuvei_sub_recurr_period';
    const PAYMENT_SUBS_RECURR_PERIOD_LABEL      = 'Recurring Period';
    
    const PAYMENT_SUBS_TRIAL_UNITS              = 'nuvei_sub_trial_units';
    const PAYMENT_SUBS_TRIAL_UNITS_LABEL        = 'Trial Units';
    const PAYMENT_SUBS_TRIAL_PERIOD             = 'nuvei_sub_trial_period';
    const PAYMENT_SUBS_TRIAL_PERIOD_LABEL       = 'Trial Period';
    
    const PAYMENT_SUBS_END_AFTER_UNITS          = 'nuvei_sub_end_after_units';
    const PAYMENT_SUBS_END_AFTER_UNITS_LABEL    = 'End After Units';
    const PAYMENT_SUBS_END_AFTER_PERIOD         = 'nuvei_sub_end_after_period';
    const PAYMENT_SUBS_END_AFTER_PERIOD_LABEL   = 'End After Period';
    
    const STORE_SUBS_DROPDOWN                   = 'nuvei_sub_store_dropdown';
    const STORE_SUBS_DROPDOWN_LABEL             = 'Nuvei Subscription Options';
    const STORE_SUBS_DROPDOWN_NAME              = 'nuvei_subscription_options';
    
    const NUVEI_SDK_AUTOCLOSE_URL               = 'https://cdn.safecharge.com/safecharge_resources/v1/websdk/autoclose.html';
    
    private $traceId;
    
    /**
     * Scope config object.
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Store manager object.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Store id.
     *
     * @var int
     */
    private $storeId;

    /**
     * Already fetched config values.
     *
     * @var array
     */
    private $config = [];
    
    /**
     * Magento version like integer.
     *
     * @var int
     */
    private $versionNum = '';
    
    /**
     * Use it to validate the redirect
     *
     * @var FormKey
     */
    private $formKey;
    
    private $directory;
    private $httpHeader;
    private $remoteIp;
    private $customerSession;
    private $cookie;
    private $productObj;
    private $productRepository;
    private $configurable;
    private $eavAttribute;
    private $fileSystem;
    
    private $clientUniqueIdPostfix = '_sandbox_apm'; // postfix for Sandbox APM payments

    /**
     * Object initialization.
     *
     * @param ScopeConfigInterface  $scopeConfig Scope config object.
     * @param StoreManagerInterface $storeManager Store manager object.
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleListInterface $moduleList
     * @param CheckoutSession $checkoutSession
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
        CheckoutSession $checkoutSession,
        UrlInterface $urlBuilder,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\Filesystem\DirectoryList $directory,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteIp,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookie,
        \Magento\Catalog\Model\Product $productObj,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Framework\Filesystem\DriverInterface $fileSystem
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->httpHeader = $httpHeader;
        $this->remoteIp = $remoteIp;
        $this->customerSession = $customerSession;

        $this->storeId              = $this->getStoreId();
        $this->storeId              = $this->getStoreId();
        $this->versionNum           = (int) str_replace('.', '', $this->productMetadata->getVersion());
        $this->formKey              = $formKey;
        $this->directory            = $directory;
        $this->cookie               = $cookie;
        $this->productObj           = $productObj;
        $this->productRepository    = $productRepository;
        $this->configurable         = $configurable;
        $this->eavAttribute         = $eavAttribute;
        $this->fileSystem           = $fileSystem;
    }

    /**
     * Return config path.
     *
     * @param string $sub_group The beginning of the Sub group
     * @return string
     */
    private function getConfigPath()
    {
        return sprintf('payment/%s/', Payment::METHOD_CODE);
    }
    
    /**
     * Prepare and save log.
     * 
     * @param mixed $data
     * @param string $title
     * @param string $log_level
     * 
     * @return void
     */
    public function createLog($data, $title = '', $log_level = 'TRACE')
    {
        if (! $this->isDebugEnabled()) {
            return;
        }
        
        $logsPath   = $this->directory->getPath('log');
        $d          = $data;
        $string     = '';
        
        if (is_bool($data)) {
            $d = $data ? 'true' : 'false';
        } elseif (is_string($data) || is_numeric($data)) {
            $d = $data;
        } elseif ('' === $data) {
            $d = 'Data is Empty.';
        } elseif (is_array($data)) {
            // do not log accounts if on prod
            if (!$this->isTestModeEnabled()) {
                if (isset($data['userAccountDetails']) && is_array($data['userAccountDetails'])) {
                    $data['userAccountDetails'] = 'account details';
                }
                if (isset($data['userPaymentOption']) && is_array($data['userPaymentOption'])) {
                    $data['userPaymentOption'] = 'user payment options details';
                }
                if (isset($data['paymentOption']) && is_array($data['paymentOption'])) {
                    $data['paymentOption'] = 'payment options details';
                }
            }
            // do not log accounts if on prod

            if (!empty($data['paymentMethods']) && is_array($data['paymentMethods'])) {
                $data['paymentMethods'] = json_encode($data['paymentMethods']);
            }
            if (!empty($data['Response data']['paymentMethods'])
                && is_array($data['Response data']['paymentMethods'])
            ) {
                $data['Response data']['paymentMethods'] = json_encode($data['Response data']['paymentMethods']);
            }

            if (!empty($data['plans']) && is_array($data['plans'])) {
                $data['plans'] = json_encode($data['plans']);
            }

            $d = $this->isTestModeEnabled() ? json_encode($data, JSON_PRETTY_PRINT) : json_encode($data);
        } elseif (is_object($data)) {
            $d = $this->isTestModeEnabled() ? json_encode($data, JSON_PRETTY_PRINT) : json_encode($data);
        } else {
            $d = $this->isTestModeEnabled() ? json_encode($data, JSON_PRETTY_PRINT) : json_encode($data);
        }
        
        $tab            = '    ';
        $utimestamp     = microtime(true);
        $timestamp      = floor($utimestamp);
        $milliseconds   = round(($utimestamp - $timestamp) * 1000000);
        $record_time    = date('Y-m-d') . 'T' . date('H:i:s') . '.' . $milliseconds . date('P');
        
        if(!$this->traceId) {
            $this->traceId = bin2hex(random_bytes(16));
        }
        
        $source_file_name   = '';
        $member_name        = '';
        $source_line_number = '';
        
        $backtrace = debug_backtrace();
        if(!empty($backtrace)) {
            if(!empty($backtrace[0]['file'])) {
                $file_path_arr  = explode(DS, $backtrace[0]['file']);
                
                if(!empty($file_path_arr)) {
                    $source_file_name = end($file_path_arr) . '|';
                }
            }
            
//            if(!empty($backtrace[0]['function'])) {
//                $member_name = $backtrace[0]['function'] . '|';
//            }
            
            if(!empty($backtrace[0]['line'])) {
                $source_line_number = $backtrace[0]['line'] . $tab;
            }
        }
        
        $string .= $record_time . $tab
            . $log_level . $tab
            . $this->traceId . $tab
            . 'Magento 2 Nuvei Checkout v' . $this->moduleList->getOne(self::MODULE_NAME)['setup_version'] . '|'
            . $source_file_name
            . $member_name
            . $source_line_number;
        
        if (!empty($title)) {
            if (is_string($title)) {
                $string .= $title;
            } else {
                if($this->isTestModeEnabled()) {
                    $string .= "\r\n" . json_encode($title, JSON_PRETTY_PRINT) . "\r\n";
                } else{
                    $string .= json_encode($title) . $tab;
                }
            }
        }

        $string .= $d . "\r\n\r\n";
        
        try {
            switch ($this->isDebugEnabled(true)) {
                case 3: // save log file per days
                    $log_file_name = 'Nuvei-' . date('Y-m-d');
                    break;
                
                case 2: // save single log file
                    $log_file_name = 'Nuvei';
                    break;
                
                case 1: // save both files
                    $log_file_name = 'Nuvei';
                    
                    $this->fileSystem->filePutContents(
                        $logsPath . DIRECTORY_SEPARATOR . 'Nuvei-' . date('Y-m-d') . '.log',
                        $string,
                        FILE_APPEND
                    );
                    break;
                
                default:
                    return;
            }
            
            if ($this->fileSystem->isDirectory($logsPath)) {
                return $this->fileSystem->filePutContents(
                    $logsPath . DIRECTORY_SEPARATOR . $log_file_name . '.log',
                    $string,
                    FILE_APPEND
                );
            }
        } catch (exception $e) {
            return;
        }
    }
    
    public function getTempPath()
    {
        return $this->directory->getPath('tmp');
    }
    
    /**
     * Function getSourceApplication
     * Get the value of one more parameter for the REST API
     *
     * @return string
     */
    public function getSourceApplication()
    {
        return 'MAGENTO_2_PLUGIN';
    }

    /**
     * Function get_device_details
     * Get browser and device based on HTTP_USER_AGENT.
     * The method is based on D3D payment needs.
     *
     * @return array $device_details
     */
    public function getDeviceDetails()
    {
        $SC_DEVICES         = ['iphone', 'ipad', 'android', 'silk', 'blackberry', 'touch', 'linux', 'windows', 'mac'];
        $SC_BROWSERS        = ['ucbrowser', 'firefox', 'chrome', 'opera', 'msie', 'edge', 'safari',
            'blackberry', 'trident'];
        $SC_DEVICES_TYPES   = ['macintosh', 'tablet', 'mobile', 'tv', 'windows', 'linux', 'tv', 'smarttv',
            'googletv', 'appletv', 'hbbtv', 'pov_tv', 'netcast.tv', 'bluray'];
        
        $device_details = [
            'deviceType'    => 'UNKNOWN', // DESKTOP, SMARTPHONE, TABLET, TV, and UNKNOWN
            'deviceName'    => '',
            'deviceOS'      => '',
            'browser'       => '',
            'ipAddress'     => '',
        ];
        
        // get ip
        try {
            $device_details['ipAddress']    = (string) $this->remoteIp->getRemoteAddress();
            $ua                                = $this->httpHeader->getHttpUserAgent();
        } catch (Exception $ex) {
            $this->createLog($ex->getMessage(), 'getDeviceDetails Exception');
            return $device_details;
        }
        
        if (empty($ua)) {
            return $device_details;
        }
        
        $user_agent = strtolower($ua);
        $device_details['deviceName'] = $ua;

        foreach ($SC_DEVICES_TYPES as $d) {
            if (strstr($user_agent, $d) !== false) {
                if (in_array($d, ['linux', 'windows', 'macintosh'], true)) {
                    $device_details['deviceType'] = 'DESKTOP';
                } elseif ('mobile' === $d) {
                    $device_details['deviceType'] = 'SMARTPHONE';
                } elseif ('tablet' === $d) {
                    $device_details['deviceType'] = 'TABLET';
                } else {
                    $device_details['deviceType'] = 'TV';
                }

                break;
            }
        }

        foreach ($SC_DEVICES as $d) {
            if (strstr($user_agent, $d) !== false) {
                $device_details['deviceOS'] = $d;
                break;
            }
        }

        foreach ($SC_BROWSERS as $b) {
            if (strstr($user_agent, $b) !== false) {
                $device_details['browser'] = $b;
                break;
            }
        }

        return $device_details;
    }
    
    /**
     * Return store manager.
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * Return store manager.
     * @return StoreManagerInterface
     */
    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    /**
     * Return store id.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Return config field value.
     *
     * @param string $fieldKey Field key.
     * @param string $sub_group The beginning of the the Sub group
     *
     * @return mixed
     */
    private function getConfigValue($fieldKey, $sub_group = '')
    {
        if (isset($this->config[$fieldKey]) === false) {
            $path = $this->getConfigPath();
            
            if (!empty($sub_group)) {
                $path .= $sub_group . '_configuration/';
            }
            
            $path .= $fieldKey;
            
            $this->config[$fieldKey] = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $this->storeId
            );
        }
        
        return $this->config[$fieldKey];
    }

    /**
     * Return bool value depends of that if payment method is active or not.
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->getConfigValue('active');
    }

    /**
     * Return title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getConfigValue('title');
    }

    /**
     * Return merchant id.
     *
     * @return string
     */
    public function getMerchantId()
    {
        if ($this->isTestModeEnabled() === true) {
            return $this->getConfigValue('sandbox_merchant_id');
        }

        return $this->getConfigValue('merchant_id');
    }

    /**
     * Return merchant site id.
     *
     * @return string
     */
    public function getMerchantSiteId()
    {
        if ($this->isTestModeEnabled() === true) {
            return $this->getConfigValue('sandbox_merchant_site_id');
        }

        return $this->getConfigValue('merchant_site_id');
    }
    
    public function getMerchantApplePayLabel()
    {
        return $this->getConfigValue('apple_pay_label', 'basic');
    }

    /**
     * Return merchant secret key.
     *
     * @return string
     */
    public function getMerchantSecretKey()
    {
        if ($this->isTestModeEnabled() === true) {
            return $this->getConfigValue('sandbox_merchant_secret_key');
        }

        return $this->getConfigValue('merchant_secret_key');
    }

    /**
     * Return hash configuration value.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->getConfigValue('hash');
    }
    
    /**
     * Return bool value depends of that if payment method sandbox mode
     * is enabled or not.
     *
     * @return bool
     */
    public function isTestModeEnabled()
    {
        if ($this->getConfigValue('mode') === Payment::MODE_LIVE) {
            return false;
        }

        return true;
    }
    
    public function showCheckoutLogo()
    {
        if ($this->getConfigValue('show_checkout_logo') == 1) {
            return true;
        }

        return false;
    }
    
    public function canUseUpos()
    {
        if ($this->customerSession->isLoggedIn() && 1 == $this->useUPOs()) {
            return true;
        }
        
        return false;
    }
    
    public function allowGuestsSubscr()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return false;
        }
        
        return true;
    }

    /**
     * Return bool value depends of that if payment method debug mode
     * is enabled or not.
     *
     * @param bool $return_value - by default is false, set true to get int value
     * @return bool
     */
    public function isDebugEnabled($return_value = false)
    {
        if ($return_value) {
            return (int) $this->getConfigValue('debug');
        }
        
        if ((int) $this->getConfigValue('debug') == 0) {
            return false;
        }
        
        return true;
    }
    
    public function useUPOs()
    {
        return (bool)$this->getConfigValue('use_upos');
    }
    
    public function useDCC()
    {
        return $this->getConfigValue('use_dcc');
    }
    
    public function getBlockedCards()
    {
        return $this->getConfigValue('block_cards', 'advanced');
    }
    
    public function getPMsBlackList()
    {
        return $this->getConfigValue('block_pms', 'advanced');
    }
    
    public function getPayButtnoText()
    {
        return $this->getConfigValue('pay_btn_text');
    }
    
    public function autoExpandPms()
    {
        return $this->getConfigValue('auto_expand_pms');
    }
    
    public function autoCloseApmPopup()
    {
        return $this->getConfigValue('auto_close_popup');
    }
    
    public function getCheckoutLogLevel()
    {
        return $this->getConfigValue('checkout_log_level');
    }
    
    public function getCheckoutTransl()
    {
        $checkout_transl = str_replace("'", '"', $this->getConfigValue('checkout_transl', 'advanced'));
        return json_decode($checkout_transl, true);
    }

    public function getSourcePlatformField()
    {
        return "Magento Plugin {$this->moduleList->getOne(self::MODULE_NAME)['setup_version']}";
    }
    
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Return full endpoint;
     *
     * @return string
     */
//    public function getEndpoint()
//    {
//        $endpoint = AbstractRequest::LIVE_ENDPOINT;
//        if ($this->isTestModeEnabled() === true) {
//            $endpoint = AbstractRequest::TEST_ENDPOINT;
//        }
//
//        return $endpoint . 'purchase.do';
//    }

    /**
     * @return string
     */
    public function getCallbackSuccessUrl()
    {
        $params = [
            'quote'        => $this->checkoutSession->getQuoteId(),
            'form_key'    => $this->formKey->getFormKey(),
        ];
        
        if ($this->versionNum != 0 && $this->versionNum < 220) {
            return $this->urlBuilder->getUrl(
                'nuvei_checkout/payment/callback_completeold',
                $params
            );
        }
        
        return $this->urlBuilder->getUrl(
            'nuvei_checkout/payment/callback_complete',
            $params
        );
    }

    /**
     * @return string
     */
    public function getCallbackPendingUrl()
    {
        $params = [
            'quote'        => $this->checkoutSession->getQuoteId(),
            'form_key'    => $this->formKey->getFormKey(),
        ];
        
        if ($this->versionNum != 0 && $this->versionNum < 220) {
            return $this->urlBuilder->getUrl(
                'nuvei_checkout/payment/callback_completeold',
                $params
            );
        }
        
        return $this->urlBuilder->getUrl(
            'nuvei_checkout/payment/callback_complete',
            $params
        );
    }

    /**
     * @return string
     */
    public function getCallbackErrorUrl()
    {
        $params = [
            'quote'        => $this->checkoutSession->getQuoteId(),
            'form_key'    => $this->formKey->getFormKey(),
        ];

        if ($this->versionNum != 0 && $this->versionNum < 220) {
            return $this->urlBuilder->getUrl(
                'nuvei_checkout/payment/callback_errorold',
                $params
            );
        }
        
        return $this->urlBuilder->getUrl(
            'nuvei_checkout/payment/callback_error',
            $params
        );
    }

    /**
     * @param int    $incrementId
     * @param int    $storeId
     * @param array    $url_params
     *
     * @return string
     */
    public function getCallbackDmnUrl($incrementId = null, $storeId = null, $url_params = [])
    {
        $url =  $this->getStoreManager()
            ->getStore(null === $incrementId ? $this->storeId : $storeId)
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        
        $params = [
            'order'     => null === $incrementId ? $this->getReservedOrderId() : $incrementId,
            'form_key'    => $this->formKey->getFormKey(),
            'quote'     => $this->checkoutSession->getQuoteId(),
        ];
        
        $params_str = '';
        
        if (!empty($url_params) && is_array($url_params)) {
            $params = array_merge($params, $url_params);
        }
        
        foreach ($params as $key => $val) {
            if (empty($val)) {
                continue;
            }
            
            $params_str .= $key . '/' . $val . '/';
        }
        
        if ($this->versionNum != 0 && $this->versionNum < 220) {
            return $url . 'nuvei_checkout/payment/callback_dmnold/' . $params_str;
        }
        
        return $url . 'nuvei_checkout/payment/callback_dmn/' . $params_str;
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->urlBuilder->getUrl('checkout/cart');
    }
    
    public function getPaymentAction()
    {
        return $this->getConfigValue('payment_action');
    }
    
    public function getQuoteId()
    {
        return (($quote = $this->checkoutSession->getQuote())) ? $quote->getId() : null;
    }
    
    public function getReservedOrderId()
    {
        $reservedOrderId = $this->checkoutSession->getQuote()->getReservedOrderId();
        if (!$reservedOrderId) {
            $this->checkoutSession->getQuote()->reserveOrderId()->save();
            $reservedOrderId = $this->checkoutSession->getQuote()->getReservedOrderId();
        }
        return $reservedOrderId;
    }

    /**
     * Get default country code.
     * @return string
     */
    public function getDefaultCountry()
    {
        return $this->scopeConfig->getValue('general/country/default', ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getQuoteCountryCode()
    {
        $quote            = $this->checkoutSession->getQuote();
        $billing        = ($quote) ? $quote->getBillingAddress() : null;
        $countryCode    = ($billing) ? $billing->getCountryId() : null;
        
        if (!$countryCode) {
            $shipping       = ($quote) ? $quote->getShippingAddress() : null;
            $countryCode    = ($shipping && $shipping->getSameAsBilling()) ? $shipping->getCountryId() : null;
        }
        
        if (!$countryCode) {
            $countryCode = $this->getDefaultCountry();
        }
        
        return $countryCode;
    }
    
    /**
     * Get base currency code from the Quote. This must be same as the Magento Base currency.
     * 
     * @return string
     */
    public function getQuoteBaseCurrency()
    {
        return $this->checkoutSession->getQuote()->getBaseCurrencyCode();
    }
    
    /**
     * Get currency code from the Quote. This must be same as the Magento store Visual currency.
     * 
     * @return string
     */
    public function getQuoteCurrency()
    {
        return $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
    }
    
    /**
     * Get store currency code. Use this when Quote is not available.
     * 
     * @return string
     */
    public function getStoreCurrency()
    {
        return trim($this->storeManager->getStore()->getCurrentCurrencyCode());
    }
    
    /**
     * Get the Quote Base Grand Total, based on Display currency.
     * 
     * @return string
     */
    public function getQuoteTotal()
    {
        return (string) number_format($this->checkoutSession->getQuote()->getBaseGrandTotal(), 2, '.', '');
    }
    
    /**
     * Get grand total in base currency.
     *
     * @return string
     */
    public function getQuoteGrandTotal()
    {
        return (string) number_format($this->checkoutSession->getQuote()->getGrandTotal(), 2, '.', '');
    }
    
    public function getQuoteBillingAddress()
    {
        $quote          = $this->checkoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();
            
        $b_f_name = $billingAddress->getFirstname();
        if (empty($b_f_name)) {
            $b_f_name = $quote->getCustomerFirstname();
        }
        
        $b_l_name = $billingAddress->getLastname();
        if (empty($b_l_name)) {
            $b_l_name = $quote->getCustomerLastname();
        }
        
        $billing_country = $billingAddress->getCountry();
        if (empty($billing_country)) {
            $billing_country = $this->getQuoteCountryCode();
        }
        if (empty($billing_country)) {
            $billing_country = $this->getDefaultCountry();
        }
        
        return [
            "firstName"    => $b_f_name,
            "lastName"  => $b_l_name,
            "address"   => $billingAddress->getStreetFull(),
            "phone"     => $billingAddress->getTelephone(),
            "zip"       => $billingAddress->getPostcode(),
            "city"      => $billingAddress->getCity(),
            'country'   => $billing_country,
            'email'     => $this->getUserEmail(),
        ];
    }
    
    public function getQuoteShippingAddress()
    {
        $shipping_address    = $this->checkoutSession->getQuote()->getShippingAddress();
        $shipping_email        = $shipping_address->getEmail();
        
        if (empty($shipping_email)) {
            $shipping_email = $this->getUserEmail();
        }
        
        return [
            "firstName"    => $shipping_address->getFirstname(),
            "lastName"  => $shipping_address->getLastname(),
            "address"   => $shipping_address->getStreetFull(),
            "phone"     => $shipping_address->getTelephone(),
            "zip"        => $shipping_address->getPostcode(),
            "city"      => $shipping_address->getCity(),
            'country'   => $shipping_address->getCountry(),
            'email'     => $shipping_email,
        ];
    }
    
    public function getNuveiUseCcOnly()
    {
        return $this->checkoutSession->getNuveiUseCcOnly();
    }
    
    public function setNuveiUseCcOnly($val)
    {
        $this->checkoutSession->setNuveiUseCcOnly($val);
    }
    
    public function setQuotePaymentMethod($method)
    {
        $quote = $this->checkoutSession->getQuote();
        $quote->getPayment()->setMethod($method);
        $quote->save();
    }
    
    /**
     * Function setClientUniqueId
     *
     * Set client unique id.
     * We change it only for Sandbox (test) mode.
     *
     * @param int $order_id - cart or order id
     * @return int|string
     */
    public function setClientUniqueId($order_id)
    {
        if (!$this->isDebugEnabled()) {
            return (int)$order_id;
        }
        
        return $order_id . '_' . time() . $this->clientUniqueIdPostfix;
    }
    
    /**
     * Function getCuid
     *
     * Get client unique id.
     * We change it only for Sandbox (test) mode.
     *
     * @param string|int $merchant_unique_id
     * @return int|string
     */
    public function getClientUniqueId($merchant_unique_id)
    {
        if (!$this->isDebugEnabled()) {
            return $merchant_unique_id;
        }
        
        if (strpos($merchant_unique_id, $this->clientUniqueIdPostfix) !== false) {
            return current(explode('_', $merchant_unique_id));
        }
        
        return $merchant_unique_id;
    }
    
    public function getUserEmail($empty_on_fail = false)
    {
        $quote    = $this->checkoutSession->getQuote();
        $email    = $quote->getBillingAddress()->getEmail();
        
        if (empty($email)) {
            $email = $quote->getCustomerEmail();
        }
        
        if (empty($email) && $empty_on_fail) {
            return '';
        }
        
        if (empty($email) && !empty($this->cookie->getCookie('guestSippingMail'))) {
            $email = $this->cookie->getCookie('guestSippingMail');
        }
        if (empty($email)) {
            $email = 'quoteID_' . $quote->getId() . '@magentoMerchant.com';
        }
        
        return $email;
    }
    
    /**
     * Search for the product with Payment Plan.
     *
     * @param int $product_id
     * @param array $params pairs option key id with option value
     *
     * @return array $return_arr
     */
    public function getProductPlanData($product_id = 0, array $params = [])
    {
        $items_data = [];
        $plan_data  = [];
        $return_arr = [];
        
        try {
            # 1. when we search in the Cart
            if (0 == $product_id && empty($params)) {
                $items = $this->checkoutSession->getQuote()->getItems();
            
                if (empty($items) || !is_array($items)) {
                    $this->createLog(
                        $items,
                        'getProductPlanData() - there are no Items in the Cart or $items is not an array'
                    );

                    return $return_arr;
                }
                
                // if there are more than 1 products in the Cart we assume there are no product with a Plan
//                if (count($items) > 1) {
//                    $this->createLog('getProductPlanData() - the Items in the Cart are more than 1. '
//                        . 'We assume there is no Product with a plan amongs them.');
//                    return $return_arr;
//                }
                
                foreach($items as $item) {
//                    $item = current($items);

                    if (!is_object($item)) {
                        $this->createLog('getProductPlanData() Error - the Item in the Cart is not an Object.');
//                        return $return_arr;
                        continue;
                    }

                    $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());

                    $this->createLog($options, 'getProductPlanData $options');

                    // stop the proccess
                    if (empty($options['info_buyRequest'])
                        || !is_array($options['info_buyRequest'])
                    ) {
//                        return $return_arr;
                        continue;
                    }

                    // 1.1 in case of configurable product
                    // 1.1.1. when we have selected_configurable_option paramter
                    if (!empty($options['info_buyRequest']['selected_configurable_option'])) {
                        $product_id = $options['info_buyRequest']['selected_configurable_option'];
                        $product    = $this->productObj->load($product_id);

                        $nuvei_sub_enabled = $product->getCustomAttribute('nuvei_sub_enabled');
                        $this->createLog(
                            $nuvei_sub_enabled,
                            'getProductPlanData get nuvei_sub_enabled on configurable product'
                        );
                        
                        if(!is_object($nuvei_sub_enabled)) {
//                            return $return_arr;
                            continue;
                        }
                    }
                    // 1.1.2. when we have super_attribute
                    elseif (!empty($options['info_buyRequest']['super_attribute'])
                        && !empty($options['info_buyRequest']['product'])
                    ) {
                        $parent     = $this->productRepository->getById($options['info_buyRequest']['product']);
                        $product    = $this->configurable->getProductByAttributes(
                            $options['info_buyRequest']['super_attribute'],
                            $parent
                        );
                        $product_id = $product->getId();

                        $nuvei_sub_enabled = $product->getCustomAttribute('nuvei_sub_enabled');
                        $this->createLog(
                            $nuvei_sub_enabled,
                            'getProductPlanData get nuvei_sub_enabled on configurable product'
                        );

                        if(!is_object($nuvei_sub_enabled)) {
//                            return $return_arr;
                            continue;
                        }
                    }

                    if(!empty($product) && 0 != $product_id) {
                        $plan_data[$product_id]     = $this->buildPlanDetailsArray($product);
                        $items_data[$product_id]    = [
                            'quantity'  => $item->getQty(),
                            'price'     => round((float) $item->getPrice(), 2),
                        ];

                        $this->createLog(
                            $plan_data,
                            'getProductPlanData $plan_data'
                        );

                        // return plan details only if the subscription is enabled
                        if (!empty($plan_data[$product_id])) {
                            $return_arr = [
                                'subs_data'     => $plan_data,
                                'items_data'    => $items_data,
                            ];
                        }
                        
                        return $return_arr;
                    }

                    // 1.2 in case of simple product
                    $product = $this->productObj->load($options['info_buyRequest']['product']);
                    $nuvei_sub_enabled  = $product->getCustomAttribute('nuvei_sub_enabled');

                    $this->createLog(
                        $product->getCustomAttribute('nuvei_sub_enabled'),
                        'getProductPlanData get nuvei_sub_enabled on simple product'
                    );

                    if(!is_object($nuvei_sub_enabled)) {
//                        return $return_arr;
                        continue;
                    }

                    $plan_data[$options['info_buyRequest']['product']] = $this->buildPlanDetailsArray($product);

                    $items_data[$item->getId()] = [
                        'quantity'  => $item->getQty(),
                        'price'     => round((float) $item->getPrice(), 2),
                    ];

                    if (!empty($plan_data[$options['info_buyRequest']['product']])) {
//                        $return_arr = [
                        return [
                            'subs_data'     => $plan_data,
                            'items_data'    => $items_data,
                        ];
                    }

//                    return $return_arr;
                }
                
                return $return_arr;
            }

            # 2. in case we pass product ID and product options as array.
            # we do not serach in the Cart and may be there is not Item data
            if (0 == $product_id || empty($params)) {
                return $return_arr;
            }

            $prod_options = [];

            // sometimes the key can be the options codes, we need the IDs
            foreach ($params as $key => $val) {
                if (is_numeric($key)) {
                    $prod_options[$key] = $val;
                    continue;
                }

                // get the option ID by its key
                $attributeId = $this->eavAttribute->getIdByCode('catalog_product', $key);

                if (!$attributeId) {
                    $this->config->createLog(
                        [$key, $attributeId],
                        'SubscriptionsHistory Error - attribute ID must be int.'
                    );
                    continue;
                }

                $prod_options[$attributeId] = $val;
            }

            if (empty($prod_options)) {
                return [];
            }

            $parent     = $this->productRepository->getById($product_id);
            $product    = $this->configurable->getProductByAttributes($prod_options, $parent);
            
//            $nuvei_sub_enabled  = $product->getCustomAttribute('nuvei_sub_enabled');
//                
//            $this->createLog(
//                $product->getCustomAttribute('nuvei_sub_enabled'),
//                'getProductPlanData get nuvei_sub_enabled on simple product'
//            );

            $plan_data = $this->buildPlanDetailsArray($product);
            
            $this->createLog(
                $plan_data,
                'getProductPlanData $plan_data of incoming product'
            );

            if (!empty($plan_data)) {
                return $plan_data;
            }

            return $return_arr;
        } catch (Exception $e) {
            $this->createLog($e->getMessage(), 'getProductPlanData() Exception:');
            return [];
        }
    }
    
    /**
     * Help function for getProductPlanData.
     * We moved here few of repeating part of code.
     *
     * @params MagentoProduct
     * @return array
     */
    private function buildPlanDetailsArray($product)
    {
        $attr = $product->getCustomAttribute(self::PAYMENT_SUBS_ENABLE);
        
        if (null === $attr) {
            $this->createLog('buildPlanDetailsArray() - there is no subscription attribute PAYMENT_SUBS_ENABLE');
            return [];
        }
        
        $subscription_enabled = $attr->getValue();
        
        if (0 == $subscription_enabled) {
            $this->createLog('buildPlanDetailsArray() - for this product the Subscription is not enabled or not set.');
            return [];
        }
        
        try {
            $recurr_unit_obj        = $product->getCustomAttribute(self::PAYMENT_SUBS_RECURR_UNITS);
            $recurr_unit            = is_object($recurr_unit_obj) ? $recurr_unit_obj->getValue() : 'month';

            $recurr_period_obj      = $product->getCustomAttribute(self::PAYMENT_SUBS_RECURR_PERIOD);
            $recurr_period          = is_object($recurr_period_obj) ? $recurr_period_obj->getValue() : 0;

            $trial_unit_obj         = $product->getCustomAttribute(self::PAYMENT_SUBS_TRIAL_UNITS);
            $trial_unit             = is_object($trial_unit_obj) ? $trial_unit_obj->getValue() : 'month';

            $trial_period_obj       = $product->getCustomAttribute(self::PAYMENT_SUBS_TRIAL_PERIOD);
            $trial_period           = is_object($trial_period_obj) ? $trial_period_obj->getValue() : 0;

            $end_after_unit_obj     = $product->getCustomAttribute(self::PAYMENT_SUBS_END_AFTER_UNITS);
            $end_after_unit         = is_object($end_after_unit_obj) ? $end_after_unit_obj->getValue() : 'month';

            $end_after_period_obj   = $product->getCustomAttribute(self::PAYMENT_SUBS_END_AFTER_PERIOD);
            $end_after_period       = is_object($end_after_period_obj) ? $end_after_period_obj->getValue() : 0;

            $rec_amount             = $product->getCustomAttribute(self::PAYMENT_SUBS_REC_AMOUNT)->getValue();

            $return_arr = [
                'planId'            => $product->getCustomAttribute(self::PAYMENT_PLANS_ATTR_NAME)->getValue(),
                'initialAmount'     => 0,
                'recurringAmount'   => number_format($rec_amount, 2, '.', ''),
                'recurringPeriod'   => [strtolower($recurr_unit)    => $recurr_period],
                'startAfter'        => [strtolower($trial_unit)     => $trial_period],
                'endAfter'          => [strtolower($end_after_unit) => $end_after_period],
            ];

            $this->createLog($return_arr, 'buildPlanDetailsArray()');

            return $return_arr;
        } catch (Exception $e) {
            $this->createLog($e->getMessage(), 'buildPlanDetailsArray() Exception');
            return [];
        }
    }
}
