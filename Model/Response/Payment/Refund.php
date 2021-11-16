<?php

namespace Nuvei\Checkout\Model\Response\Payment;

use Nuvei\Checkout\Model\Response\AbstractPayment;
use Nuvei\Checkout\Model\ResponseInterface;

/**
 * Nuvei Checkout payment refund response model.
 */
class Refund extends AbstractPayment implements ResponseInterface
{
    /**
     * @var int
     */
    protected $transactionId;

    /**
     * @var string
     */
    protected $authCode;

    /**
     * @return Refund
     */
    protected function processResponseData()
    {
        $body = $this->getBody();

        $this->transactionId = $body['transactionId'];
        $this->authCode = $body['authCode'];

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    protected function getRequestStatus()
    {
        if (parent::getRequestStatus() === false) {
            return false;
        }

        $body = $this->getBody();

        $responseTransactionStatus = strtolower(!empty($body['transactionStatus']) ? $body['transactionStatus'] : '');

        if ($responseTransactionStatus === 'error') {
            return false;
        }

        if ($responseTransactionStatus !== 'approved') {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @return string
     */
    public function getAuthCode()
    {
        return $this->authCode;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array_merge_recursive(
            parent::getRequiredResponseDataKeys(),
            [
                'transactionId',
                'authCode',
                'transactionStatus',
            ]
        );
    }

    /**
     * @return bool|string
     */
    protected function getErrorReason()
    {
        $body = $this->getBody();
        if (!empty($body['gwErrorReason'])) {
            return $body['gwErrorReason'];
        }

        return parent::getErrorReason();
    }
}
