<?php

namespace Nuvei\Checkout\Observer\Sales\Order\Invoice;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Nuvei\Checkout\Model\Payment;

/**
 * Nuvei Checkout sales order invoice pay observer.
 */
class Pay implements ObserverInterface
{
    private $readerWriter;
    
    public function __construct(\Nuvei\Checkout\Model\ReaderWriter $readerWriter)
    {
        $this->readerWriter = $readerWriter;
    }
    
    /**
     * @param Observer $observer
     *
     * @return Pay
     */
    public function execute(Observer $observer)
    {
        $this->readerWriter->createLog('Invoice Pay Observer');
        
        /** @var Invoice $invoice */
        $invoice = $observer->getInvoice();
        $invoice->setState(Invoice::STATE_OPEN);
        
        /** @var Order $order */
        $order = $invoice->getOrder();

        /** @var OrderPayment $payment */
        $payment = $order->getPayment();

        if ($payment->getMethod() !== Payment::METHOD_CODE) {
            $this->readerWriter->createLog($payment->getMethod(), 'Invoice Pay Observer Error - payment method is');
            
            return $this;
        }

        if ($invoice->getState() !== Invoice::STATE_PAID) {
            $this->readerWriter->createLog($invoice->getState(), 'Invoice Pay Observer Error - $invoice state is');
            
            return $this;
        }

        return $this;
    }
}
