<?php

namespace Nuvei\Checkout\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Nuvei\Checkout\Model\Config as ModuleConfig;
use Nuvei\Checkout\Model\Redirect\Url as RedirectUrlBuilder;

/**
 * Nuvei Checkout payment redirect controller.
 */
class Redirect extends Action
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;
    
    private $request;
    private $dataObjectFactory;
    private $cartManagement;
    private $onepageCheckout;
    private $checkoutSession;

    /**
     * Redirect constructor.
     *
     * @param Context            $context
     * @param RedirectUrlBuilder $redirectUrlBuilder
     * @param ModuleConfig       $moduleConfig
     * @param JsonFactory        $jsonResultFactory
     */
    public function __construct(
        Context $context,
        ModuleConfig $moduleConfig,
        JsonFactory $jsonResultFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\Type\Onepage $onepageCheckout,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($context);

        $this->moduleConfig         = $moduleConfig;
        $this->jsonResultFactory    = $jsonResultFactory;
        $this->request              = $request;
        $this->dataObjectFactory    = $dataObjectFactory;
        $this->cartManagement       = $cartManagement;
        $this->onepageCheckout      = $onepageCheckout;
        $this->checkoutSession      = $checkoutSession;
    }

    /**
     * @return ResponseInterface
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create()
            ->setHttpResponseCode(\Magento\Framework\Webapi\Response::HTTP_OK);

        if (!$this->moduleConfig->isActive()) {
            $msg = 'Redirect Controller: Nuvei checkout module is not active at the moment!';
            
            $this->moduleConfig->createLog($msg);
            return $result->setData(['error_message' => __($msg)]);
        }
        
        $this->moduleConfig->createLog($this->request->getParams(), 'Redirect class params:');
        
        $postData['url'] = $this->moduleConfig->getCallbackErrorUrl();
        
        // for the WebSDK
        if ($this->request->getParam('method') === 'web_sdk' && $this->request->getParam('transactionId')) {
            $postData['url'] = $this->moduleConfig->getCallbackSuccessUrl();
        }
        
        return $result->setData($postData);
    }
}
