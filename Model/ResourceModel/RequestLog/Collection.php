<?php

namespace Nuvei\Payments\Model\ResourceModel\RequestLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Nuvei Payments request log collection model.
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
            \Nuvei\Payments\Model\RequestLog::class,
            \Nuvei\Payments\Model\ResourceModel\RequestLog::class
        );
    }
}
