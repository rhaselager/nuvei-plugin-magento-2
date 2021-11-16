<?php

namespace Nuvei\Checkout\Model;

/**
 * Nuvei Checkout request interface.
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
