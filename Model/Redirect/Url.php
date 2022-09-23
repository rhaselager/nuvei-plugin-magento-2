<?php

namespace Nuvei\Checkout\Model\Redirect;

//use Magento\Checkout\Model\Session as CheckoutSession;
use Nuvei\Checkout\Model\Config as ModuleConfig;
use Magento\Framework\App\Request\Http as Http;

/**
 * Nuvei Checkout config provider model.
 */
class Url
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    
    private $request;

    /**
     * Url constructor.
     *
     * @param ModuleConfig    $moduleConfig
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        //        CheckoutSession $checkoutSession,
        Http $request
    ) {
        $this->moduleConfig     = $moduleConfig;
//        $this->checkoutSession  = $checkoutSession;
        $this->request          = $request;
    }
    
    /**
     * @return string
     */
//    public function getUrl()
//    {
//        return $this->moduleConfig->getEndpoint();
//    }

    /**
     * @return string
     */
//    public function getPostData()
//    {
//        // in case we use WebSDK just go to Success page
//        if ($this->request->getParam('method') === 'cc_card' && $this->request->getParam('transactionId')) {
//            return [
//                'url' => $this->moduleConfig->getCallbackSuccessUrl(),
//            ];
//        }
//
//        // non active case
//        return [
//            "url" => $this->moduleConfig->getEndpoint(),
//        ];
//    }
}
