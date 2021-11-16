<?php

namespace Nuvei\Checkout\Model;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\CcGenericConfigProvider;
use Nuvei\Checkout\Model\Config as ModuleConfig;
use Nuvei\Checkout\Model\Request\Factory as RequestFactory;

/**
 * Nuvei Checkout config provider model.
 */
class ConfigProvider extends CcGenericConfigProvider
{
    /**
     * @var Config
     */
    private $moduleConfig;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestFactory
     */
    private $requestFactory;
    
    private $apmsRequest;
    private $storeManager;
    private $scopeConfig;
    private $cart;
    private $assetRepo;

    /**
     * ConfigProvider constructor.
     *
     * @param CcConfig          $ccConfig
     * @param PaymentHelper     $paymentHelper
     * @param Config            $moduleConfig
     * @param CustomerSession   $customerSession
     * @param UrlInterface      $urlBuilder
     * @param RequestFactory    $requestFactory
     * @param array             $methodCodes
     * @param AssetRepository   $assetRepo
     */
    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        ModuleConfig $moduleConfig,
        CustomerSession $customerSession,
        UrlInterface $urlBuilder,
        RequestFactory $requestFactory,
        array $methodCodes,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\View\Asset\Repository $assetRepo
    ) {
        $this->moduleConfig     = $moduleConfig;
        $this->customerSession  = $customerSession;
        $this->urlBuilder       = $urlBuilder;
        $this->requestFactory   = $requestFactory;
        $this->storeManager     = $storeManager;
        $this->scopeConfig      = $scopeConfig;
        $this->cart             = $cart;
        $this->assetRepo        = $assetRepo;

        $methodCodes = array_merge_recursive(
            $methodCodes,
            [Payment::METHOD_CODE]
        );

        parent::__construct(
            $ccConfig,
            $paymentHelper,
            $methodCodes
        );
    }

    /**
     * Return config array.
     *
     * @return array
     */
    public function getConfig()
    {
        if (!$this->moduleConfig->isActive()) {
            return [];
        }
        
        $locale            = $this->scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        $config = [
            'payment' => [
                Payment::METHOD_CODE => [
                    'getMerchantPaymentMethodsUrl' => $this->urlBuilder
                        ->getUrl('nuvei_payments/payment/GetMerchantPaymentMethods'),
                    
                    'redirectUrl'               => $this->urlBuilder->getUrl('nuvei_payments/payment/redirect'),
                    'paymentApmUrl'             => $this->urlBuilder->getUrl('nuvei_payments/payment/apm'),
                    'getUPOsUrl'                => $this->urlBuilder->getUrl('nuvei_payments/payment/GetUpos'),
                    'getUpdateOrderUrl'         => $this->urlBuilder->getUrl('nuvei_payments/payment/OpenOrder'),
                    'getRemoveUpoUrl'           => $this->urlBuilder->getUrl('nuvei_payments/payment/DeleteUpo'),
                    'checkoutLogoUrl'           => $this->assetRepo->getUrl("Nuvei_Checkout::images/nuvei.png"),
                    'checkoutApplePayBtn'       => $this->assetRepo->getUrl("Nuvei_Checkout::images/ApplePay-Button.png"),
                    
                    'countryId'                 => $this->moduleConfig->getQuoteCountryCode(),
                    'updateQuotePM'             => $this->urlBuilder->getUrl('nuvei_payments/payment/UpdateQuotePaymentMethod'),
                    'useUPOs'                   => $this->moduleConfig->canUseUpos(),
                    'submitUserTokenForGuest'   => ($this->moduleConfig->allowGuestsSubscr()
                        && !empty($this->moduleConfig->getProductPlanData())) ? 1 : 0,
                    // we need this for the WebSDK
                    'merchantSiteId'            => $this->moduleConfig->getMerchantSiteId(),
                    'merchantId'                => $this->moduleConfig->getMerchantId(),
                    'isTestMode'                => $this->moduleConfig->isTestModeEnabled(),
                    'locale'                    => substr($locale, 0, 2),
                    'webMasterId'               => $this->moduleConfig->getSourcePlatformField(),
                    'sourceApplication'         => $this->moduleConfig->getSourceApplication(),
                    'userTokenId'               => $this->moduleConfig->getQuoteBillingAddress()['email'],
                    'applePayLabel'             => $this->moduleConfig->getMerchantApplePayLabel(),
                    'currencyCode'              => trim($this->storeManager->getStore()->getCurrentCurrencyCode()),
//                    'total'                   => (string) number_format($this->cart->getQuote()->getGrandTotal(), 2, '.', ''),
                ],
            ],
        ];
        
        return $config;
    }
}
