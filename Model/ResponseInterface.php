<?php

namespace Nuvei\Checkout\Model;

/**
 * Nuvei Checkout response interface.
 */
interface ResponseInterface
{
    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function process();
}
