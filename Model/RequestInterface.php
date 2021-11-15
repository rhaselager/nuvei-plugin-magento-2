<?php

namespace Nuvei\Payments\Model;

/**
 * Nuvei Payments request interface.
 */
interface RequestInterface
{
    /**
     * Process current request type.
     *
     * @return RequestInterface
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function process();
}
