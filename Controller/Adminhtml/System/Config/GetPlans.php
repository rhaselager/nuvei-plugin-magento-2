<?php

namespace Nuvei\Payments\Controller\Adminhtml\System\Config;

use Nuvei\Payments\Model\AbstractRequest;

class GetPlans extends \Magento\Backend\App\Action
{
    protected $jsonResultFactory;
    protected $moduleConfig;
    protected $requestFactory;
    protected $objManager;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Nuvei\Payments\Model\Config $moduleConfig,
        \Nuvei\Payments\Model\Request\Factory $requestFactory
    ) {
        parent::__construct($context);
        
        $this->jsonResultFactory    = $jsonResultFactory;
        $this->moduleConfig         = $moduleConfig;
        $this->requestFactory       = $requestFactory;
    }
    
    public function execute()
    {
        $result = $this->jsonResultFactory->create()
            ->setHttpResponseCode(\Magento\Framework\Webapi\Response::HTTP_OK);

        if (!$this->moduleConfig->isActive()) {
            $this->moduleConfig->createLog('Nuvei payments module is not active at the moment!');
           
            return $result->setData([
                'error_message' => __('Nuvei payments module is not active at the moment!')
            ]);
        }
        
        $request = $this->requestFactory->create(AbstractRequest::GET_MERCHANT_PAYMENT_PLANS_METHOD);

        try {
            $resp = $request->process();
        } catch (PaymentException $e) {
            $this->moduleConfig->createLog($e->getMessage(), 'GetPlans Exception:');
            
            return $result->setData([
                "success"  => 0,
                "message"  => "Error"
            ]);
        }
        
        return $result->setData([
            "success" => $resp ? 1 : 0,
            "message" => "Success"
        ]);
    }
}
