<?php

/**
 * Description of PreventAddToCart
 *
 * A product with a rebilling plan must stay alone in a Cart and an Order.
 */

namespace Nuvei\Checkout\Plugin;

class PreventAddToCart
{
    private $config;
    private $request;
    private $messanger;
    private $product_obj;
    private $productRepository;
    private $productTypeInstance;
    private $eavModel;
    private $configurableProduct;

    public function __construct(
        \Nuvei\Checkout\Model\Config $config,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Message\ManagerInterface $messanger,
        \Magento\Catalog\Model\Product $product_obj,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productTypeInstance,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $eavModel,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProduct
    ) {
        $this->config               = $config;
        $this->request              = $request;
        $this->messanger            = $messanger;
        $this->product_obj          = $product_obj;
        $this->productRepository    = $productRepository;
        $this->productTypeInstance  = $productTypeInstance;
        $this->eavModel             = $eavModel;
        $this->configurableProduct  = $configurableProduct;
    }

    public function beforeAddProduct(\Magento\Checkout\Model\Cart $subject, $productInfo, $requestInfo = null)
    {
        try {
            # 1. first search for SC plan in the items in the cart
            if (!empty($this->config->getProductPlanData())) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('You can not add this product to product with a Payment Plan.')
                );
            }
            
            $payment_enabled    = false;
            $cartItemsCount     = $subject->getQuote()->getItemsCount();
            $error_msg_2        = __('You can not add a product with Payment Plan to another products.');
            $error_msg_3        = __('Only Registered users can purchase Products with Plans.');
            
            # 2. then search for SC plan in the incoming item when there are products in the cart
            // 2.1 when we have configurable product with option attribute
            if (!empty($requestInfo['super_attribute'])) {
                // get the configurable product by its attributes
                $conProd = $this->configurableProduct
                    ->getProductByAttributes($requestInfo['super_attribute'], $productInfo);
                
                if (is_object($conProd)) {
                    $payment_enabled = (bool) $conProd->getData(\Nuvei\Checkout\Model\Config::PAYMENT_SUBS_ENABLE);
                }
            } else { // 2.2 when we have simple peoduct without options
                $payment_enabled = (bool) $productInfo->getData(\Nuvei\Checkout\Model\Config::PAYMENT_SUBS_ENABLE);
            }
            
            // the incoming product has plan
            if ($payment_enabled) {
                // check for guest user
                if (!$this->config->allowGuestsSubscr()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($error_msg_3));
                }
                
                if ($cartItemsCount > 0) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($error_msg_2));
                }
            }
        } catch (Exception $e) {
            $this->config->createLog($e->getMessage(), 'Exception:');
        }
    }
}
