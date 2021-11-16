<?php

namespace Nuvei\Checkout\Model\Config\Source;

class EnabledDisabled extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        $this->_options = [
            [
                'label' => __('Enabled'),
                'value' => '1'
            ],
            [
                'label' => __('Disabled'),
                'value' => '0'
            ],
         ];
        
        return $this->_options;
    }
}
