<?php

namespace Nuvei\Checkout\Block\Adminhtml;

use Nuvei\Checkout\Model\Config;

class ReadPlans extends \Magento\Backend\Block\Template
{
    protected $_template = 'Nuvei_Checkout::readPlans.phtml';
    
    private $config;
    private $readerWriter;
    private $request;
    private $product;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\App\RequestInterface $request,
        \Nuvei\Checkout\Model\Config $config,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter,
        array $data = []
    ) {
        parent::__construct($context, $data);
        
        $this->config       = $config;
        $this->readerWriter = $readerWriter;
        $this->request      = $request;
        $this->product      = $product;
    }
    
    /**
     *
     * @return string
     */
    public function getPaymentPlans()
    {
        $prod_id        = $this->request->getParam('id');
        $product        = $this->product->load($prod_id);
        $subs_pan_id    = (int) $product->getData(Config::PAYMENT_PLANS_ATTR_NAME);
        
        $file_name = $this->config->getTempPath() . DIRECTORY_SEPARATOR
            . \Nuvei\Checkout\Model\Config::PAYMENT_PLANS_FILE_NAME;
        
        if (!$this->readerWriter->isReadable($file_name)) {
            return '';
        }
        
        $file_cont  = json_decode($this->readerWriter->readFile($file_name), true);
        $plans      = [];
        
        if (empty($file_cont['plans']) || !is_array($file_cont['plans'])) {
            return '';
        }
        
        foreach ($file_cont['plans'] as $data) {
            $plans[$data['planId']] = $data;
        }
        
        $data = [
            'plans'         => $plans,
            'selected_plan' => $subs_pan_id <= 0 ? 1 : $subs_pan_id,
        ];
        
        $this->readerWriter->createLog($data, '$plans');
        
        return json_encode($data);
    }
}
