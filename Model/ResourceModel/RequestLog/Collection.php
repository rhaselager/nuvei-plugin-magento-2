<?php

namespace Nuvei\Checkout\Model\ResourceModel\RequestLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Nuvei Checkout request log collection model.
 */
class Collection extends AbstractCollection
{
    /**
     * Resource model construct that should be used for object initialization.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_init(
            \Nuvei\Checkout\Model\RequestLog::class,
            \Nuvei\Checkout\Model\ResourceModel\RequestLog::class
        );
    }
}
