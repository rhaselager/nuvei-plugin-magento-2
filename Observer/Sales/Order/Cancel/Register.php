<?php

namespace Nuvei\Payments\Observer\Sales\Order\Cancel;

use Nuvei\Payments\Model\Payment;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Nuvei Payments sales order void observer.
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
        /** @var OrderPayment $payment */
        $payment = $observer->getPayment();

        if ($payment->getMethod() !== Payment::METHOD_CODE) {
            return $this;
        }

        /** @var Order $order */
        $order = $payment->getOrder();

        $order->setStatus(Payment::SC_VOIDED);

        return $this;
    }
}
