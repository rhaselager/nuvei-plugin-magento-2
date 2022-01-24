<?php

namespace Nuvei\Payments\Observer;

use Nuvei\Payments\Model\Config;
use Nuvei\Payments\Model\Payment;
use Magento\Quote\Model\QuoteRepository;

/**
 * Check quote after user login for products with a Payment plan.
 * If merged quote contains product with a Payment plan and other products,
 * remove all products except the one with the product plan. In case there are
 * more of them, remove all.
 */
class QuoteMerge implements \Magento\Framework\Event\ObserverInterface
{
    private $config;
    private $quoteRepository;
    
    public function __construct(
        Config $config,
        QuoteRepository $quoteRepository
    ) {
        $this->config           = $config;
        $this->quoteRepository  = $quoteRepository;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        try {
            $quote = $this->config->getCheckoutSession()->getQuote();
            $quote_items = $quote->getAllVisibleItems();
            
//            $items = $observer->getEvent()->getSource()->getAllVisibleItems();
//            $this->config->createLog(count($items), 'QuoteMerge before getSource items cnt');
//            $this->config->createLog(current($items)->getId(), 'QuoteMerge before first item id');
            
            $incoming_quote = $observer->getQuote();
            $incoming_items = $incoming_quote->getAllVisibleItems();
            
            foreach(array_merge($quote_items, $incoming_items) as $item) {
                $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                
                $this->config->createLog(
                    [
                        'item id'       => $item->getId(), 
                        'item options'  => $options
                    ],
                    'QuoteMerges loop item data'
                );
                
                // stop the proccess
                if (empty($options['info_buyRequest'])
                    || !is_array($options['info_buyRequest'])
                ) {
                    $this->config->createLog('QuoteMerges continue 1');
                    $prods_without_plan[] = $item->getId();
                    continue;
                }
                
                // stop the proccess
                if (empty($options['info_buyRequest']['selected_configurable_option'])) {
                    $this->config->createLog('QuoteMerges continue 2');
                    $prods_without_plan[] = $item->getId();
                    continue;
                }
                elseif (empty($options['info_buyRequest']['super_attribute'])) { // stop the proccess
                    $this->config->createLog('QuoteMerges continue 3');
                    $prods_without_plan[] = $item->getId();
                    continue;
                }
                
                // we found product with a Payment plan, clean incoming data
                $this->config->createLog('QuoteMerges remove all items');
                $quote->removeAllItems();
                break;
            }
        } catch(Exception $e) {
            $this->config->createLog($e->getMessage(), 'QuoteMerges Exception');
        }
    }

}
