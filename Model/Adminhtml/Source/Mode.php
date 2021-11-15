<?php

namespace Nuvei\Payments\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Nuvei Payments mode source model.
 */
class Mode implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ''            => __('Please, select an option...'),
            'live'      => __('Live'),
            'sandbox'    => __('Sandbox'),
        ];
    }
}
