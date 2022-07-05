<?php

namespace Nuvei\Checkout\Observer\Sales\Order\Invoice;

use Nuvei\Checkout\Model\Payment;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Nuvei Checkout sales order invoice after save observer.
 *
 * We use this observer to get the Invoice ID and pass it into the Settle request.
 */
class AfterSave implements ObserverInterface
{
    protected $objectManager;
    protected $jsonResultFactory;
    
    private $config;
    private $paymentRequestFactory;
    private $requestFactory;
    
    public function __construct(
        \Nuvei\Checkout\Model\Config $config,
        \Nuvei\Checkout\Model\Request\Payment\Factory $paymentRequestFactory,
        \Nuvei\Checkout\Model\Request\Factory $requestFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
    ) {
        $this->config                   = $config;
        $this->paymentRequestFactory    = $paymentRequestFactory;
        $this->requestFactory           = $requestFactory;
        $this->objectManager            = $objectManager;
        $this->jsonResultFactory        = $jsonResultFactory;
    }
    
    /**
     * @param Observer $observer
     *
     * @return Register
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Invoice $invoice */
            $invoice = $observer->getInvoice();
            
            if (!is_object($invoice)) {
                $this->config->createLog('Invoice AfterSave Observer - $invoice is not an object.');
                return $this;
            }
            
            // if the invoice is Paid, we already made Settle request.
            if (in_array($invoice->getState(), [Invoice::STATE_PAID, Invoice::STATE_CANCELED])) {
                $this->config->createLog(
                    $invoice->getId(),
                    'Invoice AfterSave Observer - the invoice already paid or canceled.'
                );
                
                return $this;
            }

            /** @var Order $order */
            $order = $invoice->getOrder();
            
            if (!is_object($order)) {
                $this->config->createLog('Invoice AfterSave Observer - $order is not an object.');
                return $this;
            }

            /** @var OrderPayment $payment */
            $payment = $order->getPayment();
            
            if (!is_object($payment)) {
                $this->config->createLog('Invoice AfterSave Observer - $payment is not an object.');
                return $this;
            }

            if ($payment->getMethod() !== Payment::METHOD_CODE) {
                $this->config->createLog(
                    $payment->getMethod(),
                    'Invoice AfterSave Observer Error - payment method is'
                );

                return $this;
            }

            // Settle request
            $authCode                = '';
            $ord_trans_addit_info    = $payment->getAdditionalInformation(Payment::ORDER_TRANSACTIONS_DATA);
            
            // probably a Sale
            if (!is_array($ord_trans_addit_info)
                || empty($ord_trans_addit_info)
                || count($ord_trans_addit_info) < 1
            ) {
                return $this;
            }
            
            foreach ($ord_trans_addit_info as $trans) {
                if (strtolower($trans[Payment::TRANSACTION_STATUS]) == 'approved') {
                    if (strtolower($trans[Payment::TRANSACTION_TYPE]) == 'sale') {
                        $this->config->createLog('After Save Invoice observer - Sale');
                        return $this;
                    }

                    if (strtolower($trans[Payment::TRANSACTION_TYPE]) == 'auth') {
                        $this->config->createLog('After Save Invoice observer - Auth');
                        $authCode = $trans[Payment::TRANSACTION_AUTH_CODE];
                        break;
                    }
                }
            }
            
            if (empty($authCode)) {
                $this->config->createLog(
                    $ord_trans_addit_info,
                    'Invoice AfterSave Observer - $authCode is empty.'
                );
                
                $payment->setIsTransactionPending(true); // TODO do we need this
                return $this;
            }
            
            $request = $this->objectManager->create(\Nuvei\Checkout\Model\Request\SettleTransaction::class);

            $request
                ->setPayment($payment)
                ->setInvoiceId($invoice->getId())
                ->setInvoiceAmount($invoice->getGrandTotal())
                ->process();
            // Settle request END
        } catch (Exception $e) {
            $this->config->createLog($e->getMessage(), 'Invoice AfterSave Exception');
        }
        
        return $this;
    }
}
