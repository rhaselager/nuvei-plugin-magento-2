<?php

namespace Nuvei\Payments\Model\Config\Source;

class SubscriptionUnits extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        $this->_options = [
            [
                'label' => __('DAY'),
                'value' => 'day'
            ],
            [
                'label' => __('MONTH'),
                'value' => 'month'
            ],
            [
                'label' => __('YEAR'),
                'value' => 'year'
            ],
         ];
        
        return $this->_options;
    }
}
