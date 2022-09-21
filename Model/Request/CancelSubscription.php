<?php

namespace Nuvei\Checkout\Model\Request;

use Nuvei\Checkout\Model\AbstractRequest;
use Nuvei\Checkout\Model\RequestInterface;

/**
 * Nuvei Checkout Cancel Subscription request model.
 */
class CancelSubscription extends AbstractRequest implements RequestInterface
{
    protected $subscr_id;

    /**
     * @return AbstractResponse
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws PaymentException
     */
    public function process()
    {
        return $this->sendRequest(true, true);
    }
    
    public function setSubscrId($subscr_id = 0)
    {
        $this->subscr_id = $subscr_id;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return self::CANCEL_SUBSCRIPTION_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getParams()
    {
        $params = array_merge_recursive(
            ['subscriptionId' => $this->subscr_id],
            parent::getParams()
        );
        
        return $params;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getChecksumKeys()
    {
        return [
            'merchantId',
            'merchantSiteId',
            'subscriptionId',
            'timeStamp',
        ];
    }
}
