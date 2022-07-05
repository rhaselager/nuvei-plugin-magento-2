<?php

namespace Nuvei\Checkout\Controller\Payment\Callback;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Nuvei Checkout payment place controller.
 */
class Error extends \Magento\Framework\App\Action\Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
    /**
     * @var ModuleConfig
     */
//    private $moduleConfig;
    
    private $readerWriter;

    /**
     * Error constructor.
     *
     * @param Context          $context
     * @param ModuleConfig     $moduleConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
//        \Nuvei\Checkout\Model\Config $moduleConfig,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter
    ) {
        parent::__construct($context);

//        $this->moduleConfig = $moduleConfig;
        $this->readerWriter = $readerWriter;
    }
    
    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return ResultInterface
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $this->readerWriter->createLog($params, 'Error Callback Response: ');
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
