<?php

namespace Nuvei\Checkout\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Nuvei Checkout request log resource model.
 */
class RequestLog extends AbstractDb
{
    /**
     * Resource model construct that should be used for object initialization.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('nuvei_checkout_api_request_log', 'request_id');
    }
}
