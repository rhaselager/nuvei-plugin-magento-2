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
        
        $locale = $this->scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        // call OpenOrder here to get the SessionToken
//        $request    = $this->requestFactory->create(AbstractRequest::OPEN_ORDER_METHOD);
//        $resp       = $request->process();
        
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
        
        // get email
        $email = $this->cart->getQuote()->getBillingAddress()->getEmail();
        
        if(empty($email)) {
            $email = $this->cart->getQuote()->getCustomerEmail();
        }
        // get email END
        
        $config = [
            'payment' => [
                Payment::METHOD_CODE => [
                    'getMerchantPaymentMethodsUrl' => $this->urlBuilder
                        ->getUrl('nuvei_checkout/payment/GetMerchantPaymentMethods'),
                    
                    'redirectUrl'               => $this->urlBuilder->getUrl('nuvei_checkout/payment/redirect'),
                    'paymentApmUrl'             => $this->urlBuilder->getUrl('nuvei_checkout/payment/apm'),
                    'getUpdateOrderUrl'         => $this->urlBuilder->getUrl('nuvei_checkout/payment/OpenOrder'),
                    'successUrl'                => $this->moduleConfig->getCallbackSuccessUrl(),
                    'checkoutLogoUrl'           => $this->assetRepo->getUrl("Nuvei_Checkout::images/nuvei.png"),
                    'isTestMode'                => $this->moduleConfig->isTestModeEnabled(),
                    'countryId'                 => $this->moduleConfig->getQuoteCountryCode(),
                    'updateQuotePM'             => $this->urlBuilder->getUrl('nuvei_checkout/payment/UpdateQuotePaymentMethod'),
                    
                    'submitUserTokenForGuest'   => ($this->moduleConfig->allowGuestsSubscr()
                        && !empty($this->moduleConfig->getProductPlanData())) ? 1 : 0,
                    
                    'nuveiCheckoutParams'       => [
//                        'sessionToken'          => $resp->sessionToken,
                        'env'                   => $this->moduleConfig->isTestModeEnabled() ? 'test' : 'prod',
                        'merchantId'            => $this->moduleConfig->getMerchantId(),
                        'merchantSiteId'        => $this->moduleConfig->getMerchantSiteId(),
//                        'country'               => '', // set it in the js
                        'currency'              => trim($this->storeManager->getStore()->getCurrentCurrencyCode()),
//                        'amount'                => '', // set it in the js
                        'renderTo'              => '#nuvei_checkout',
                    //            'onResult'              => ', // pass it in the JS, showNuveiCheckout()
                    //            'userTokenId'           => '',
                        'useDCC'                =>  $this->moduleConfig->useDCC(),
                        'strict'                => false,
                        'savePM'                => $this->moduleConfig->canUseUpos(),
                    //            'subMethod'           => '',
                    //            'pmWhitelist'           => [],
                        'blockCards'            => $blocked_cards,
                        'pmBlacklist'           => $this->moduleConfig->getPMsBlackList(),
                        'alwaysCollectCvv'      => true,
//                        'fullName'              => '', // set it in the js
                        'email'                 => $email,
                        'payButton'             => $this->moduleConfig->getPayButtnoText(),
                        'showResponseMessage'   => false, // shows/hide the response popups
                        'locale'                => substr($locale, 0, 2),
                        'autoOpenPM'            => (bool) $this->moduleConfig->autoExpandPms(),
                        'logLevel'              => $this->moduleConfig->getCheckoutLogLevel(),
                        'maskCvv'               => true,
                        'i18n'                  => $this->moduleConfig->getCheckoutTransl(),
                    ],
                ],
            ],
        ];
        
        return $config;
    }
}
