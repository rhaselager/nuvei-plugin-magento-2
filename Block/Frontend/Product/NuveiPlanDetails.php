<?php

namespace Nuvei\Checkout\Block\Frontend\Product;

use Nuvei\Checkout\Model\Config;

class NuveiPlanDetails extends \Magento\Catalog\Block\Product\View
{
    private $urlBuilder;
    private $configurable;
    private $eavAttribute;
    
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
        
        $this->configurable = $configurable;
        $this->urlBuilder   = $urlBuilder;
        $this->eavAttribute = $eavAttribute;
    }

    /**
     * Function isProductWithPlan
     *
     * Checks if the product has Nuvei Plan options
     *
     * @return bool
     */
    public function isProductWithPlan()
    {
        $product                        = $this->getProduct();
        $is_product_with_nuvei_options  = 0;
        $nuvei_subscr_enable            = $product->getCustomAttribute(Config::PAYMENT_SUBS_ENABLE);
        
        if (null !== $nuvei_subscr_enable && 1 == $nuvei_subscr_enable->getValue()) {
            $is_product_with_nuvei_options = 1;
        }
        
        $productAttributeOptions = $this->configurable->getConfigurableAttributesAsArray($product);
        
        foreach ($productAttributeOptions as $data) {
            if (isset($data['attribute_code'])
                && false !== strpos($data['attribute_code'], 'nuvei')
            ) {
                $is_product_with_nuvei_options = 1;
                break;
            }
        }
        
        return $is_product_with_nuvei_options;
    }
    
    /**
     * Function getTexts
     *
     * Get translated texts.
     *
     * @return array
     */
    public function getTexts()
    {
        $nuvei_prod_attr_code   = \Nuvei\Checkout\Model\Config::STORE_SUBS_DROPDOWN_NAME;
        $nuvei_prod_attr_id     = $this->eavAttribute->getIdByCode('catalog_product', $nuvei_prod_attr_code);
        
        return [
            'table_title'       => __('Nuvei Plan Details'),
            'rec_length'        => __('Recurring length'),
            'rec_period'        => __('Recurring period'),
            'rec_amount'        => __('Recurring amount'),
            'trial_period'      => __('Trial period'),
            'nuvei_ajax_url'    => $this->urlBuilder->getUrl('nuvei_checkout/frontend/order_subscriptionsHistory'),
            'nuvei_prod_id'     => $this->getProduct()->getId(),
//            'nuvei_attr_code'   => $nuvei_prod_attr_code,
            'nuvei_attr_id'     => $nuvei_prod_attr_id,
        ];
    }
}
