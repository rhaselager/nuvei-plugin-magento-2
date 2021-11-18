<?php

namespace Nuvei\Checkout\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Nuvei Checkout payment action source model.
 */
class PayButtonText implements ArrayInterface
{
    /**
     * Possible actions on order place.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'amountButton'  => __('Shows the amount'),
            'textButton'    => __('Shows the payment method'),
        ];
    }
}
