<?php

namespace Nuvei\Payments\Model\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Nuvei\Payments\Lib\Http\Client\Curl;
use Nuvei\Payments\Model\AbstractResponse;
use Nuvei\Payments\Model\ResponseInterface;

/**
 * Nuvei Payments response factory model.
 */
class Factory
{
    /**
     * Set of requests.
     *
     * @var array
     */
    private $invokableClasses = [
        AbstractResponse::TOKEN_HANDLER             => \Nuvei\Payments\Model\Response\Token::class,
        AbstractResponse::PAYMENT_SETTLE_HANDLER    => \Nuvei\Payments\Model\Response\Payment\Settle::class,
        AbstractResponse::GET_USER_DETAILS_HANDLER  => \Nuvei\Payments\Model\Response\GetUserDetails::class,
        AbstractResponse::PAYMENT_REFUND_HANDLER    => \Nuvei\Payments\Model\Response\Payment\Refund::class,
        AbstractResponse::PAYMENT_VOID_HANDLER      => \Nuvei\Payments\Model\Response\Payment\Cancel::class,
        
        AbstractResponse::GET_MERCHANT_PAYMENT_METHODS_HANDLER
            => \Nuvei\Payments\Model\Response\GetMerchantPaymentMethods::class,
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
     * Create response model.
     *
     * @param string            $type
     * @param int               $requestId
     * @param Curl|null         $curl
     * @param OrderPayment|null $payment
     *
     * @return ResponseInterface
     * @throws LocalizedException
     */
    public function create(
        $type,
        $requestId,
        $curl = null,
        $payment = null
    ) {
        $className = !empty($this->invokableClasses[$type])
            ? $this->invokableClasses[$type]
            : null;

        if ($className === null) {
            throw new LocalizedException(
                __('%1 type is not supported.', $type)
            );
        }

        $model = $this->objectManager->create(
            $className,
            [
                'requestId' => $requestId,
                'curl' => $curl,
                'orderPayment' => $payment,
            ]
        );
        if (!$model instanceof ResponseInterface) {
            throw new LocalizedException(
                __(
                    '%1 doesn\'t implement \Nuvei\Payments\Mode\ResponseInterface',
                    $className
                )
            );
        }

        return $model;
    }
}
