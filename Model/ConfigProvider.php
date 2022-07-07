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
//    private $customerSession;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestFactory
     */
//    private $requestFactory;
    
    private $apmsRequest;
//    private $storeManager;
    private $scopeConfig;
//    private $cart;
    private $assetRepo;
    private $paymentsPlans;
    private $readerWriter;

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
//        CustomerSession $customerSession,
        UrlInterface $urlBuilder,
//        RequestFactory $requestFactory,
//        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
//        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Nuvei\Checkout\Model\PaymentsPlans $paymentsPlans,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter,
        array $methodCodes
    ) {
        $this->moduleConfig     = $moduleConfig;
//        $this->customerSession  = $customerSession;
        $this->urlBuilder       = $urlBuilder;
//        $this->requestFactory   = $requestFactory;
//        $this->storeManager     = $storeManager;
        $this->scopeConfig      = $scopeConfig;
//        $this->cart             = $cart;
        $this->assetRepo        = $assetRepo;
        $this->paymentsPlans    = $paymentsPlans;
        $this->readerWriter     = $readerWriter;

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
            $this->readerWriter->createLog($this->moduleConfig->isActive(), 'Mudule is not active');
            
            return $config = [
                'payment' => [
                    Payment::METHOD_CODE => [
                        'isActive' => 0,
                    ],
                ],
            ];
        }
        
        $locale = $this->scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        # blocked_cards
        $blocked_cards     = [];
        $blocked_cards_str = $this->moduleConfig->getBlockedCards();
        
        // clean the string from brakets and quotes
        $blocked_cards_str = str_replace('],[', ';', $blocked_cards_str);
        $blocked_cards_str = str_replace('[', '', $blocked_cards_str);
        $blocked_cards_str = str_replace(']', '', $blocked_cards_str);
        $blocked_cards_str = str_replace('"', '', $blocked_cards_str);
        $blocked_cards_str = str_replace("'", '', $blocked_cards_str);
        
        if (!empty($blocked_cards_str)) {
            $blockCards_sets = explode(';', $blocked_cards_str);

            if (count($blockCards_sets) == 1) {
                $blocked_cards = explode(',', current($blockCards_sets));
            } else {
                foreach ($blockCards_sets as $elements) {
                    $blocked_cards[] = explode(',', $elements);
                }
            }
        }
        # blocked_cards END
        
        $blocked_pms = $this->moduleConfig->getPMsBlackList();
        
        $billing_address    = $this->moduleConfig->getQuoteBillingAddress();
        $payment_plan_data  = $this->paymentsPlans->getProductPlanData();
        $save_pm            = $show_upo
                            = $this->moduleConfig->canUseUpos();
        
        if(!empty($payment_plan_data)) {
            $save_pm = 'always';
        }
        
        // TODO - there is a problem getting this setting
//        $checkout_logo = $this->moduleConfig->showCheckoutLogo()
//            ? $this->assetRepo->getUrl("Nuvei_Checkout::images/nuvei.png") : '';
        $checkout_logo = '';
        
        $config = [
            'payment' => [
                Payment::METHOD_CODE => [
                    'getMerchantPaymentMethodsUrl' => $this->urlBuilder
                        ->getUrl('nuvei_checkout/payment/GetMerchantPaymentMethods'),
                    
                    'redirectUrl'               => $this->urlBuilder->getUrl('nuvei_checkout/payment/redirect'),
                    'paymentApmUrl'             => $this->urlBuilder->getUrl('nuvei_checkout/payment/apm'),
                    'getUpdateOrderUrl'         => $this->urlBuilder->getUrl('nuvei_checkout/payment/OpenOrder'),
                    'successUrl'                => $this->moduleConfig->getCallbackSuccessUrl(),
                    'checkoutLogoUrl'           => $checkout_logo,
                    'isTestMode'                => $this->moduleConfig->isTestModeEnabled(),
                    'countryId'                 => $this->moduleConfig->getQuoteCountryCode(),
                    'updateQuotePM'             => $this->urlBuilder
                        ->getUrl('nuvei_checkout/payment/UpdateQuotePaymentMethod'),
                    
                    'isPaymentPlan'             => !empty($payment_plan_data) ? 1 : 0,
                    'useDevSdk'                 => $this->moduleConfig->useDevSdk(),
                    
                    // we will set some of the parameters in the JS file
                    'nuveiCheckoutParams' => [
//                        'sessionToken'              => '',
                        'env'                       => $this->moduleConfig->isTestModeEnabled() ? 'test' : 'prod',
                        'merchantId'                => $this->moduleConfig->getMerchantId(),
                        'merchantSiteId'            => $this->moduleConfig->getMerchantSiteId(),
                        'country'                   => $billing_address['country'],
                        'currency'                  => $this->moduleConfig->getQuoteBaseCurrency(),
//                        'amount'                    => $this->moduleConfig->getQuoteBaseTotal(),
                        'renderTo'                  => '#nuvei_checkout',
                        'useDCC'                    =>  $this->moduleConfig->useDCC(),
                        'strict'                    => false,
                        'savePM'                    => $save_pm,
                        'showUserPaymentOptions'    => $show_upo,
//                        'pmBlacklist'               => $this->moduleConfig->getPMsBlackList(),
//                        'pmWhitelist'               => null,
                        'alwaysCollectCvv'          => true,
                        'fullName'                  => '', // set it in the js
                        'email'                     => $billing_address['email'],
                        'payButton'                 => $this->moduleConfig->getPayButtnoText(),
                        'showResponseMessage'       => false, // shows/hide the response popups
                        'locale'                    => substr($locale, 0, 2),
                        'autoOpenPM'                => (bool) $this->moduleConfig->autoExpandPms(),
                        'logLevel'                  => $this->moduleConfig->getCheckoutLogLevel(),
                        'maskCvv'                   => true,
                        'i18n'                      => $this->moduleConfig->getCheckoutTransl(),
                        'blockCards'                => $blocked_cards,
                        'billingAddress'            => $billing_address,
                        'userData'                  => ['billingAddress' => $billing_address],
                    ],
                ],
            ],
        ];
        
        if(!empty($blocked_pms) && null !== $blocked_pms) {
            $config['payment'][Payment::METHOD_CODE]['nuveiCheckoutParams']['pmBlacklist'] = $blocked_pms;
        }
        
        if(1 == $config['payment'][Payment::METHOD_CODE]['useDevSdk']) {
            $config['payment'][Payment::METHOD_CODE]['nuveiCheckoutParams']['webSdkEnv'] = 'dev';
        }
        
        $this->readerWriter->createLog($config, 'config for the checkout');
        
        return $config;
    }
}
