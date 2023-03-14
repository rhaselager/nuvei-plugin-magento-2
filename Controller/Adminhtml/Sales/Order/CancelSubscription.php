<?php

namespace Nuvei\Checkout\Controller\Adminhtml\Sales\Order;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;

/**
 * TODO - Can not be used because of problems with Magento admin sercurity key.
 * 
 * @author Nuvei
 */
//class CancelSubscription extends \Magento\Backend\App\Action
//class CancelSubscription extends \Magento\Backend\App\Action implements HttpGetActionInterface
//class CancelSubscription extends \Magento\Backend\App\Action implements CsrfAwareActionInterface
//class CancelSubscription implements HttpGetActionInterface
class CancelSubscription
{
    private $readerWriter;
    protected $_publicActions = ['index', 'execute'];

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter,
        ForwardFactory $forwardFactory
    ) {
        $this->resultPageFactory    = $resultPageFactory;
        $this->readerWriter         = $readerWriter;
        $this->forwardFactory = $forwardFactory;

//        $this->readerWriter->createLog('class CancelSubscription __construct');
//        $resultPage = $this->resultPageFactory->create();
//        $resultPage->getConfig()->getTitle()->prepend(__('Hello World'));
//        return $resultPage;
        die('test');
//        parent::__construct($context);
    }
    
    /**
     * @inheritDoc
     */
//    public function createCsrfValidationException(
//        RequestInterface $request
//    ): ?InvalidRequestException {
//        return null;
//    }

    /**
     * @inheritDoc
     */
//    public function validateForCsrf(RequestInterface $request): ?bool
//    {
//        return true;
//    }
    
    public function execute()
    {
        $this->readerWriter->createLog('class CancelSubscription execute');
        
        echo 'Its working';
        
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Hello World'));
        return $resultPage;
        
//        $this->messageManager->addSuccessMessage(__('The credit memo has been canceled.'));
        
//        $resultRedirect->setPath('sales/*/view', ['order_id' => $order->getId()]);
//        return $resultRedirect;
        
//        $resultRedirect = $this->resultRedirectFactory->create();
//        $resultRedirect->setPath('sales/view');
//        return $resultRedirect;
        
        $forward = $this->forwardFactory->create();
        return $forward->forward('defaultNoRoute');
    }
    
    protected function _isAllowed()
    {
        return true;
    }
    
}
