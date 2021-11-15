<?php

namespace Nuvei\Payments\Controller\Payment\Callback;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Nuvei\Payments\Model\Config as ModuleConfig;
use Nuvei\Payments\Model\Logger as Logger;

/**
 * Nuvei Payments payment place controller.
 */
class ErrorOld extends Action
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * Error constructor.
     *
     * @param Context       $context
     * @param ModuleConfig    $moduleConfig
     */
    public function __construct(
        Context $context,
        ModuleConfig $moduleConfig
    ) {
        parent::__construct($context);

        $this->moduleConfig = $moduleConfig;
    }
    
    /**
     * @return ResultInterface
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $this->moduleConfig->createLog($params, 'Error Callback Response: ');
        $this->messageManager->addErrorMessage(
            __('Your payment failed.')
        );
        
        $form_key        = filter_input(INPUT_GET, 'form_key');
        $resultRedirect    = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        
        $resultRedirect->setUrl(
            $this->_url->getUrl('checkout/cart')
            . (!empty($form_key) ? '?form_key=' . $form_key : '')
        );

        return $resultRedirect;
    }
}
