<?php

namespace Nuvei\Payments\Model;

/**
 * Nuvei Payments response interface.
 */
interface ResponseInterface
{
    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function process();
}
