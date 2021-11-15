<?php

namespace Nuvei\Payments\Model\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Nuvei\Payments\Model\AbstractRequest;
use Nuvei\Payments\Model\RequestInterface;

/**
 * Nuvei Payments request factory model.
 */
class Factory
{
    /**
     * Set of requests.
     *
     * @var array
     */
    private $invokableClasses = [
        AbstractRequest::GET_MERCHANT_PAYMENT_METHODS_METHOD
            => \Nuvei\Payments\Model\Request\GetMerchantPaymentMethods::class,
        
        AbstractRequest::CREATE_SUBSCRIPTION_METHOD
            => \Nuvei\Payments\Model\Request\CreateSubscription::class,
        
        AbstractRequest::CANCEL_SUBSCRIPTION_METHOD
            => \Nuvei\Payments\Model\Request\CancelSubscription::class,
        
        AbstractRequest::GET_USER_DETAILS_METHOD            => \Nuvei\Payments\Model\Request\GetUserDetails::class,
        AbstractRequest::OPEN_ORDER_METHOD                  => \Nuvei\Payments\Model\Request\OpenOrder::class,
        AbstractRequest::UPDATE_ORDER_METHOD                => \Nuvei\Payments\Model\Request\UpdateOrder::class,
        AbstractRequest::PAYMENT_APM_METHOD                 => \Nuvei\Payments\Model\Request\PaymentApm::class,
        AbstractRequest::GET_UPOS_METHOD                    => \Nuvei\Payments\Model\Request\GetUserUPOs::class,
        AbstractRequest::DELETE_UPOS_METHOD                 => \Nuvei\Payments\Model\Request\DeleteUPO::class,
        AbstractRequest::GET_MERCHANT_PAYMENT_PLANS_METHOD  => \Nuvei\Payments\Model\Request\GetPlansList::class,
        AbstractRequest::CREATE_MERCHANT_PAYMENT_PLAN       => \Nuvei\Payments\Model\Request\CreatePlan::class,
        AbstractRequest::SETTLE_METHOD                      => \Nuvei\Payments\Model\Request\Settle::class,
    ];

    /**
     * Object manager object.
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Construct
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create request model.
     *
     * @param string $method - the name of the method
     * @param array $args - arguments to pass
     *
     * @return RequestInterface
     * @throws LocalizedException
     */
    public function create($method, $args = [])
    {
        $className = !empty($this->invokableClasses[$method])
            ? $this->invokableClasses[$method]
            : null;

        if ($className === null) {
            throw new LocalizedException(
                __('%1 method is not supported.', $method)
            );
        }

        $model = $this->objectManager->create($className, $args);
        
        if (!$model instanceof RequestInterface) {
            throw new LocalizedException(
                __(
                    '%1 doesn\'t implement \Nuvei\Payments\Model\RequestInterface',
                    $className
                )
            );
        }

        return $model;
    }
}
