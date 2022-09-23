<?php

namespace Nuvei\Checkout\Setup;

use Magento\Sales\Model\Order\StatusFactory as OrderStatusFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order;

/**
 * Nuvei Checkout upgrade data.
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var OrderStatusFactory
     */
    private $orderStatusFactory;
    
    private $resourceConnection;
//    private $attributeSetFactory;
    private $install;

    /**
     * Object constructor.
     *
     * @param OrderStatusFactory $orderStatusFactory
     */
    public function __construct(
        OrderStatusFactory $orderStatusFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        //        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory,
        \Magento\Framework\Setup\SchemaSetupInterface $install
    ) {
        $this->orderStatusFactory   = $orderStatusFactory;
        $this->resourceConnection   = $resourceConnection;
        $this->eavSetupFactory      = $eavSetupFactory;
//        $this->attributeSetFactory  = $attributeSetFactory;
        $this->install              = $install;
    }

    /**
     * {@inheritdoc}
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @return void
     * @throws \Exception
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         * To have Configurable attributes we must set the following parameters:
         *
         * 'source'         => some_source_class
         * 'global'         => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL
         * 'user_defined'   => true
         * 'visible'        => true,
         */
        
        // try to add main plugin table if not exists, and remove old plugin table if exists
        //$this->install($this->install, $context);
        
        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        
//        $eavSetup->removeAttribute(
//            \Magento\Catalog\Model\Product::ENTITY,
//            'nuvei_test_attr'
//        );
        
        /**
         * example for update
        if (version_compare($context->getVersion(), '3.0.1', '<')) {
            $this->resourceConnection->getConnection()->query(
                "UPDATE sales_order_status "
                . "SET status = 'nuvei_voided' "
                . "WHERE sales_order_status.status = 'vmes';"
            );
        }
         */
        
        // add few new Order States
        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $scVoided = $this->orderStatusFactory->create()
                ->setData('status', 'nuvei_voided')
                ->setData('label', 'Nuvei Voided')
                ->save();
            $scVoided->assignState(Order::STATE_PROCESSING, false, true);

            $scSettled = $this->orderStatusFactory->create()
                ->setData('status', 'nuvei_settled')
                ->setData('label', 'Nuvei Settled')
                ->save();
            $scSettled->assignState(Order::STATE_PROCESSING, false, true);

            $scAuth = $this->orderStatusFactory->create()
                ->setData('status', 'nuvei_auth')
                ->setData('label', 'Nuvei Auth')
                ->save();
            $scAuth->assignState(Order::STATE_PROCESSING, false, true);
            
            $scProcessing = $this->orderStatusFactory->create()
                ->setData('status', 'nuvei_processing')
                ->setData('label', 'Nuvei Processing')
                ->save();
            $scProcessing->assignState(Order::STATE_PROCESSING, false, true);
            
            $scRefunded = $this->orderStatusFactory->create()
                ->setData('status', 'nuvei_refunded')
                ->setData('label', 'Nuvei Refunded')
                ->save();
            $scRefunded->assignState(Order::STATE_PROCESSING, false, true);
            
            $scCanceled = $this->orderStatusFactory->create()
                ->setData('status', 'nuvei_canceled')
                ->setData('label', 'Nuvei Canceled')
                ->save();
            $scCanceled->assignState(Order::STATE_CANCELED, false, true);
            
            # Admin > Product > Nuvei Subscription details
            // Enable subscription
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_ENABLE,
                [
                    'label'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_ENABLE_LABEL,
                    'global'    => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'source'    => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'group'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_GROUP,
                    
                    'type'                      => 'int',
                    'input'                     => 'boolean',
                    'visible'                   => true,
                    'required'                  => false,
                    'user_defined'              => true,
                    'default'                   => '',
                    'searchable'                => false,
                    'filterable'                => false,
                    'visible_on_front'          => false,
                    'used_in_product_listing'   => true,
                    'sort_order'                => 10,
                    'class'                     => 'sc_enable_subscr',
                ]
            );

            // Plan IDs
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                \Nuvei\Checkout\Model\Config::PAYMENT_PLANS_ATTR_NAME,
                [
                    'label'     => \Nuvei\Checkout\Model\Config::PAYMENT_PLANS_ATTR_LABEL,
                    'global'    => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'source'    => \Nuvei\Checkout\Model\Config\Source\PaymentPlansOptions::class,
                    'group'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_GROUP,
                    
                    'type'                      => 'int',
                    'input'                     => 'select',
                    'visible'                   => true,
                    'required'                  => false,
                    'user_defined'              => false,
                    'default'                   => '',
                    'searchable'                => false,
                    'filterable'                => false,
                    'visible_on_front'          => false,
                    'used_in_product_listing'   => true,
                    'option'                    => ['values' => []],
                    'sort_order'                => 20,
                ]
            );

            // Recurring Amount
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_REC_AMOUNT,
                [
                    'label'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_REC_AMOUNT_LABEL,
                    'global'    => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'group'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_GROUP,
                    
                    'type'                      => 'decimal',
                    'input'                     => 'price',
                    'visible'                   => true,
                    'required'                  => false,
                    'user_defined'              => true,
                    'default'                   => '0',
                    'searchable'                => false,
                    'filterable'                => false,
                    'visible_on_front'          => false,
                    'used_in_product_listing'   => true,
                    'sort_order'                => 40,
                ]
            );

            // Recurring Units
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_RECURR_UNITS,
                [
                    'label'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_RECURR_UNITS_LABEL,
                    'global'    => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'source'    => \Nuvei\Checkout\Model\Config\Source\SubscriptionUnits::class,
                    'group'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_GROUP,
                    
                    'type'                      => 'text',
                    'input'                     => 'select',
                    'visible'                   => true,
                    'required'                  => false,
                    'user_defined'              => false,
                    'default'                   => 'day',
                    'searchable'                => false,
                    'filterable'                => false,
                    'visible_on_front'          => false,
                    'used_in_product_listing'   => false,
                    'option'                    => ['values' => []],
                    'sort_order'                => 50,
                ]
            );

            // Recurring Period
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_RECURR_PERIOD,
                [
                    'label'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_RECURR_PERIOD_LABEL,
                    'global'    => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'group'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_GROUP,
                    
                    'type'                      => 'int',
                    'input'                     => 'text',
                    'visible'                   => true,
                    'required'                  => false,
                    'user_defined'              => true,
                    'default'                   => '0',
                    'searchable'                => false,
                    'filterable'                => false,
                    'visible_on_front'          => false,
                    'used_in_product_listing'   => false,
                    'sort_order'                => 60,
                    'note'                      => __('Integer value, bigger than 0.'),
                ]
            );

            // Trial Units
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_TRIAL_UNITS,
                [
                    'label'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_TRIAL_UNITS_LABEL,
                    'global'    => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'source'    => \Nuvei\Checkout\Model\Config\Source\SubscriptionUnits::class,
                    'group'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_GROUP,
                    
                    'type'                      => 'text',
                    'input'                     => 'select',
                    'visible'                   => true,
                    'required'                  => false,
                    'user_defined'              => false,
                    'default'                   => 'day',
                    'searchable'                => false,
                    'filterable'                => false,
                    'visible_on_front'          => false,
                    'used_in_product_listing'   => false,
                    'option'                    => ['values' => []],
                    'sort_order'                => 70,
                ]
            );

            // Trial Period
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_TRIAL_PERIOD,
                [
                    'label'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_TRIAL_PERIOD_LABEL,
                    'global'    => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'group'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_GROUP,
                    
                    'type'                      => 'int',
                    'input'                     => 'text',
                    'visible'                   => true,
                    'required'                  => false,
                    'user_defined'              => true,
                    'default'                   => '0',
                    'searchable'                => false,
                    'filterable'                => false,
                    'visible_on_front'          => false,
                    'used_in_product_listing'   => false,
                    'sort_order'                => 80,
                    'note'                      => __('Integer value, bigger or equal to 0.'),
                ]
            );

            // End After Units
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_END_AFTER_UNITS,
                [
                    'label'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_END_AFTER_UNITS_LABEL,
                    'global'    => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'source'    => \Nuvei\Checkout\Model\Config\Source\SubscriptionUnits::class,
                    'group'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_GROUP,
                    
                    'type'                      => 'text',
                    'input'                     => 'select',
                    'visible'                   => true,
                    'required'                  => false,
                    'user_defined'              => false,
                    'default'                   => 'day',
                    'searchable'                => false,
                    'filterable'                => false,
                    'visible_on_front'          => false,
                    'used_in_product_listing'   => false,
                    'option'                    => ['values' => []],
                    'sort_order'                => 90,
                ]
            );

            // End After Period
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_END_AFTER_PERIOD,
                [
                    'label'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_END_AFTER_PERIOD_LABEL,
                    'global'    => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'group'     => \Nuvei\Checkout\Model\Config::PAYMENT_SUBS_GROUP,
                    
                    'type'                      => 'int',
                    'input'                     => 'text',
                    'visible'                   => true,
                    'required'                  => false,
                    'user_defined'              => true,
                    'default'                   => '0',
                    'searchable'                => false,
                    'filterable'                => false,
                    'visible_on_front'          => false,
                    'used_in_product_listing'   => false,
                    'sort_order'                => 100,
                    'note'                      => __('Integer value, bigger than 0.'),
                ]
            );
            # Admin > Product > Nuvei Subscription details END
        }
        
        $setup->endSetup();
    }
}
