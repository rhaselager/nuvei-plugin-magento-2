<?php

namespace Nuvei\Payments\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Nuvei Payments mode source model.
 */
class Debug implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            3 => __('Split the log by days'),
            2 => __('Save in single file'),
            1 => __('Save both files'),
            0 => __('No'),
        ];
    }
}
