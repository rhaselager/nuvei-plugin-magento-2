<?php

namespace Nuvei\Payments\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Nuvei\Payments\Model\AbstractRequest;

class DeleteUpo extends Action
{
    /**
     * @var RedirectUrlBuilder
     */
    private $redirectUrlBuilder;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var RequestFactory
     */
    private $requestFactory;
    
    /**
     * Redirect constructor.
     *
     * @param Context                $context
     * @param RedirectUrlBuilder    $redirectUrlBuilder
     * @param ModuleConfig            $moduleConfig
     * @param JsonFactory            $jsonResultFactory
     * @param RequestFactory        $requestFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Nuvei\Payments\Model\Redirect\Url $redirectUrlBuilder,
        \Nuvei\Payments\Model\Config $moduleConfig,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Nuvei\Payments\Model\Request\Factory $requestFactory
    ) {
        parent::__construct($context);

        $this->redirectUrlBuilder   = $redirectUrlBuilder;
        $this->moduleConfig         = $moduleConfig;
        $this->jsonResultFactory    = $jsonResultFactory;
        $this->requestFactory       = $requestFactory;
    }
    
    public function execute()
    {
        $result = $this->jsonResultFactory->create()->setHttpResponseCode(\Magento\Framework\Webapi\Response::HTTP_OK);

        if (!$this->moduleConfig->isActive()) {
            $this->moduleConfig->createLog('Nuvei payments module is not active at the moment!');
            
            return $result->setData([
                'error_message' => __('Nuvei payments module is not active at the moment!')
            ]);
        }
        
        try {
            $request    = $this->requestFactory->create(AbstractRequest::DELETE_UPOS_METHOD);
            $resp        = $request
                ->setUpoId($this->getRequest()->getParam('upoId'))
                ->process();
        } catch (Exception $ex) {
            return $result->setData(["success" => 0]);
        }
        
        return $result->setData(["success" => $resp === 'success' ? 1 : 0]);
    }
}
