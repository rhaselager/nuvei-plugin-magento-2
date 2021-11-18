<?php

namespace Nuvei\Checkout\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Nuvei Checkout payment action source model.
 */
class UseDcc implements ArrayInterface
{
    /**
     * Possible actions on order place.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ''          => __('Please, select an option...'),
            'Enable'    => __('Enabled'),
            'Force'     => __('Enabled and expanded'),
            'False'     => __('Disabled'),
        ];
    }
}
