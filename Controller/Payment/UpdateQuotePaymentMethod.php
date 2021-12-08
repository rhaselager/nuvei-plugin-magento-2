<?php

namespace Nuvei\Checkout\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Nuvei\Checkout\Model\Config as ModuleConfig;

/**
 * Nuvei Checkout UpdateQuotePaymentMethod controller.
 */
class UpdateQuotePaymentMethod extends Action
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * Redirect constructor.
     *
     * @param Context               $context
     * @param ModuleConfig          $moduleConfig
     * @param JsonFactory           $jsonResultFactory
     */
    public function __construct(
        Context $context,
        ModuleConfig $moduleConfig,
        JsonFactory $jsonResultFactory
    ) {
        parent::__construct($context);

        $this->moduleConfig         = $moduleConfig;
        $this->jsonResultFactory    = $jsonResultFactory;
    }

    /**
     * @return ResponseInterface
     */
    public function execute()
    {
        $result = $this
            ->jsonResultFactory
            ->create()
            ->setHttpResponseCode(\Magento\Framework\Webapi\Response::HTTP_OK);
        
        $this->moduleConfig->createLog(
            $this->getRequest()->getParam('paymentMethod'),
            'Class UpdateQuotePaymentMethod'
        );
        
        $this->moduleConfig->setQuotePaymentMethod($this->getRequest()->getParam('paymentMethod'));
        
        return $result->setData([
            "error"     => 0,
            "message"   => "Success"
        ]);
    }
}
