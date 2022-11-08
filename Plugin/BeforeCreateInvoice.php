<?php

/**
 * @author Nuvei
 */

namespace Nuvei\Checkout\Plugin;

use Nuvei\Checkout\Model\Payment;

class BeforeCreateInvoice
{
    private $objectManager;
    private $readerWriter;
    
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter
    ) {
        $this->objectManager    = $objectManager;
        $this->readerWriter     = $readerWriter;
    }
    
    public function beforeToInvoice(\Magento\Sales\Model\Convert\Order $subject, \Magento\Sales\Model\Order $order)
    {
        $this->readerWriter->createLog($_REQUEST, 'BeforeCreateInvoice->beforeToInvoice()');
        
        // the second condition is when the merchant click on "UpdateQty's" button
        if (empty($_REQUEST['invoice']['items']) || isset($_REQUEST['isAjax'])) {
            return;
        }
        
        $items_for_invoice = [];
        $inv_amount = 0;

        foreach ($_REQUEST['invoice']['items'] as $id => $available) {
            if(1 == $available) {
                $items_for_invoice[] = $id;
            }
        }

        foreach ($order->getAllItems() as $item) {
            if (in_array($item->getId(), $items_for_invoice)) {
                $inv_amount += round($item->getBasePriceInclTax(), 2) 
                    - round($item->getBaseDiscountAmount(), 2);
            }

        }
            
        if (0 == $inv_amount) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Calculated Invoice amoutn is Zero.'));
        }
        
        /** @var OrderPayment $payment */
        $payment = $order->getPayment();

        if (!is_object($payment)) {
            $this->readerWriter->createLog('beforeToInvoice - $payment is not an object.');
            throw new \Magento\Framework\Exception\LocalizedException(__('The Payment is not an object.'));
        }

        if ($payment->getMethod() !== Payment::METHOD_CODE) {
            $this->readerWriter->createLog($payment->getMethod(), 'beforeToInvoice - payment method is');
            throw new \Magento\Framework\Exception\LocalizedException(__('The Payment does not belong to Nuvei.'));
        }

        // Settle request
        $authCode               = '';
        $ord_trans_addit_info   = $payment->getAdditionalInformation(Payment::ORDER_TRANSACTIONS_DATA);
        
        // probably a Sale
        if (!is_array($ord_trans_addit_info)
            || empty($ord_trans_addit_info)
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(__('There is no Order Transaction data.'));
        }
        
        foreach ($ord_trans_addit_info as $trans) {
            if (strtolower($trans[Payment::TRANSACTION_STATUS]) == 'approved'
                && strtolower($trans[Payment::TRANSACTION_TYPE]) == 'auth'
            ) {
                $authCode = $trans[Payment::TRANSACTION_AUTH_CODE];
                break;
            }
        }
        
        if (empty($authCode)) {
            $this->readerWriter->createLog($ord_trans_addit_info, 'BeforeCreateInvoice - $authCode is empty.');
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The Order Auth transaction missing the Auth code parameter.'));
        }
        
        $request = $this->objectManager->create(\Nuvei\Checkout\Model\Request\SettleTransaction::class);

        $resp = $request
            ->setPayment($payment)
//            ->setInvoiceId($invoice->getId())
            ->setInvoiceAmount($inv_amount)
            ->process();
        
        if(empty($resp['transactionStatus']) || 'APPROVED' != $resp['transactionStatus']) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Settle request error, you can check Nuvei log for more information.'));
        }
        
        
        return;
    }
}
