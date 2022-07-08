<?php

namespace Nuvei\Checkout\Observer\Sales\Order\Refund;

use Nuvei\Checkout\Model\Payment;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Nuvei Checkout sales order refund register observer.
 */
class Register implements ObserverInterface
{
    /**
     * @param Observer $observer
     *
     * @return Register
     */
    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order      = $creditmemo->getOrder();
        $payment    = $order->getPayment();

        if ($payment->getMethod() !== Payment::METHOD_CODE) {
            return $this;
        }
        
        $order->setStatus(Payment::SC_PROCESSING);

        return $this;
    }
}
