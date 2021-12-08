<?php

namespace Nuvei\Checkout\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Nuvei\Checkout\Model\AbstractRequest;
use Nuvei\Checkout\Model\Config as ModuleConfig;
use Nuvei\Checkout\Model\Request\Factory as RequestFactory;

/**
 * Nuvei Checkout GetMerchantPaymentMethods controller.
 */
class GetMerchantPaymentMethods extends Action implements ArrayInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * Redirect constructor.
     *
     * @param Context            $context
     * @param ModuleConfig       $moduleConfig
     * @param RequestFactory     $requestFactory
     */
    public function __construct(
        Context $context,
        ModuleConfig $moduleConfig,
        RequestFactory $requestFactory
    ) {
        parent::__construct($context);

        $this->moduleConfig     = $moduleConfig;
        $this->requestFactory   = $requestFactory;
        
        $this->moduleConfig->createLog('GetMerchantPaymentMethods()');
    }

    /**
     * @return ResponseInterface
     */
    public function execute()
    {
        if (!$this->moduleConfig->isActive()) {
            $this->moduleConfig->createLog('GetMerchantPaymentMethods error - Nuvei checkout module is not active at the moment!');
            
            return [];
        }
        
        $pms_array = [];
        
        foreach ($this->getApmMethods() as $data) {
            if(empty($data['paymentMethod'])) {
                continue;
            }
            
            $title = $data['paymentMethod'];
            
            if(!empty($data['paymentMethodDisplayName']['message'])) {
                $title = $data['paymentMethodDisplayName']['message'];
            }
            
            $pms_array[] = [
                'value' => $data['paymentMethod'],
                'label' => $data['paymentMethodDisplayName']['message']
            ];
        }
        
        return $pms_array;
    }

    /**
     * Return AMP Methods.
     * We pass both parameters from JS via Ajax request
     *
     * @return array
     */
    private function getApmMethods()
    {
        try {
            $request    = $this->requestFactory->create(AbstractRequest::GET_MERCHANT_PAYMENT_METHODS_METHOD);
            $apmMethods = $request
                ->setBillingAddress($this->getRequest()->getParam('billingAddress'))
                ->process();

            return $apmMethods->getPaymentMethods();
        } catch (Exception $e) {
            $this->moduleConfig->createLog($e->getMessage(), 'Get APMs exception');
            return [];
        }
    }

    public function toOptionArray(): array {
        $pms = $this->execute();
//        $this->moduleConfig->createLog($pms);
        return $pms;
    }

}
