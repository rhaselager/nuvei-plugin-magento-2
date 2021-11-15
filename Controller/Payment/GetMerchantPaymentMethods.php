<?php

namespace Nuvei\Payments\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Nuvei\Payments\Model\AbstractRequest;
use Nuvei\Payments\Model\Config as ModuleConfig;
use Nuvei\Payments\Model\Request\Factory as RequestFactory;

/**
 * Nuvei Payments GetMerchantPaymentMethods controller.
 */
class GetMerchantPaymentMethods extends Action
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
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * Redirect constructor.
     *
     * @param Context            $context
     * @param ModuleConfig       $moduleConfig
     * @param JsonFactory        $jsonResultFactory
     * @param RequestFactory     $requestFactory
     */
    public function __construct(
        Context $context,
        ModuleConfig $moduleConfig,
        JsonFactory $jsonResultFactory,
        RequestFactory $requestFactory
    ) {
        parent::__construct($context);

        $this->moduleConfig         = $moduleConfig;
        $this->jsonResultFactory    = $jsonResultFactory;
        $this->requestFactory       = $requestFactory;
    }

    /**
     * @return ResponseInterface
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create()->setHttpResponseCode(\Magento\Framework\Webapi\Response::HTTP_OK);

        if (!$this->moduleConfig->isActive()) {
            $this->moduleConfig->createLog('Nuvei payments module is not active at the moment!');
            
            return $result->setData([
                'error_message' => __('Nuvei payments module is not active at the moment!')
            ]);
        }
        
        $applePayData   = null;
        $apmMethodsData = $this->getApmMethods();
        $upos           = $this->getUpos($apmMethodsData);
        
        foreach ($apmMethodsData['apmMethods'] as $k => $d) {
            if ('ppp_ApplePay' == $d["paymentMethod"]) {
                $applePayData = $d;
                unset($apmMethodsData['apmMethods'][$k]);
                
                $this->moduleConfig->createLog($applePayData, 'GetMerchantPaymentMethods $applePayData');
                break;
            }
        }
        
        return $result->setData([
            "error"         => 0,
            "apmMethods"    => $apmMethodsData['apmMethods'],
            "applePayData"  => $applePayData,
            "upos"          => $upos,
            "sessionToken"  => $apmMethodsData['sessionToken'],
            "message"       => "Success"
        ]);
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

            return [
                'apmMethods'    => $apmMethods->getPaymentMethods(),
                'sessionToken'  => $apmMethods->getSessionToken(),
            ];
        } catch (Exception $e) {
            $this->moduleConfig->createLog($e->getMessage(), 'Get APMs exception');
            return [
                'apmMethods'    => [],
                'sessionToken'  => [],
            ];
        }
    }
    
    private function getUpos($apmMethodsData)
    {
        $upos = [];
        
        if (!$this->moduleConfig->canUseUpos() || empty($apmMethodsData['apmMethods'])) {
            return $upos;
        }
        
        try {
            $request    = $this->requestFactory->create(AbstractRequest::GET_UPOS_METHOD);
            $resp       = $request->process();
        } catch (Exception $e) {
            $this->moduleConfig->createLog($e->getMessage(), 'Get UPOs exception');
        }
        
        if (empty($resp) || !is_array($resp)) {
            return $upos;
        }

        foreach ($resp as $upo_data) {
            foreach ($apmMethodsData['apmMethods'] as $apm_data) {
                if ($apm_data['paymentMethod'] === $upo_data['paymentMethodName']) {
                    $upo_data['logoURL']    = !empty($apm_data['logoURL']) ? $apm_data['logoURL'] : '';
                    $upo_data['name']        = !empty($apm_data['paymentMethodDisplayName'][0]['message'])
                        ? $apm_data['paymentMethodDisplayName'][0]['message'] : '';

                    $label = '';
                    if ($upo_data['paymentMethodName'] == 'cc_card') {
                        if (!empty($upo_data['upoData']['ccCardNumber'])) {
                            $label = $upo_data['upoData']['ccCardNumber'];
                        }
                    } elseif (!empty($upo_data['upoName'])) {
                        $label = $upo_data['upoName'];
                    }

                    $upo_data['store_label'] = $label;

                    $upos[] = $upo_data;
                    break;
                }
            }
        }
        
        return $upos;
    }
}
