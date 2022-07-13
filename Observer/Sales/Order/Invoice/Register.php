<?php

namespace Nuvei\Checkout\Observer\Sales\Order\Invoice;

use Nuvei\Checkout\Model\Payment;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Nuvei Checkout sales order invoice register observer.
 *
 * Here we just set status to pending, and will wait for the DMN to confirm the payment.
 *
 */
class Register implements ObserverInterface
{
//    private $config;
//    
//    public function __construct(\Nuvei\Checkout\Model\Config $config)
//    {
//        $this->config = $config;
//    }
    
    private $readerWriter;
    
    public function __construct(\Nuvei\Checkout\Model\ReaderWriter $readerWriter)
    {
        $this->readerWriter = $readerWriter;
    }
    
    /**
     * Function execute
     *
     * @param Observer $observer
     * @return Register
     */
    public function execute(Observer $observer)
    {
        $this->readerWriter->createLog('Invoice Register Observer.');
        
        /** @var Order $order */
        $order = $observer->getOrder();
        
        if (!is_object($order)) {
            return $this;
        }

        /** @var OrderPayment $payment */
        $payment = $order->getPayment();
        
        if (!is_object($payment)) {
            return $this;
        }

        if ($payment->getMethod() !== Payment::METHOD_CODE) {
            $this->readerWriter->createLog($payment->getMethod(), 'Invoice Register - payment method is not Nuvei, but');
            
            return $this;
        }

        /** @var Invoice $invoice */
        $invoice    = $observer->getInvoice();
        $inv_state  = Invoice::STATE_OPEN; // in case of auth we will change it when DMN come
        
        $invoice->setState($inv_state);
        
        return $this;
    }
}
