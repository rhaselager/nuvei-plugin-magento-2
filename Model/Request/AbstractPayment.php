<?php

namespace Nuvei\Payments\Model\Request;

use Magento\Sales\Model\Order\Payment as OrderPayment;
use Nuvei\Payments\Lib\Http\Client\Curl;
use Nuvei\Payments\Model\AbstractRequest;
use Nuvei\Payments\Model\Config;
use Nuvei\Payments\Model\Logger as Logger;
use Nuvei\Payments\Model\Request\Factory as RequestFactory;
use Nuvei\Payments\Model\Request\Payment\Factory as PaymentRequestFactory;
use Nuvei\Payments\Model\Response\Factory as ResponseFactory;
use Nuvei\Payments\Model\ResponseInterface;

/**
 * Nuvei Payments abstract payment request model.
 */
abstract class AbstractPayment extends AbstractRequest
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var PaymentRequestFactory
     */
    protected $paymentRequestFactory;

    /**
     * @var OrderPayment
     */
    protected $orderPayment;

    /**
     * @var float
     */
    protected $amount;

    /**
     * AbstractPayment constructor.
     *
     * @param Logger      $logger
     * @param Config                $config
     * @param Curl                  $curl
     * @param RequestFactory        $requestFactory
     * @param PaymentRequestFactory $paymentRequestFactory
     * @param ResponseFactory       $responseFactory
     * @param OrderPayment|null     $orderPayment
     * @param float|null            $amount
     */
    public function __construct(
        Logger $logger,
        Config $config,
        Curl $curl,
        RequestFactory $requestFactory,
        PaymentRequestFactory $paymentRequestFactory,
        ResponseFactory $responseFactory,
        OrderPayment $orderPayment,
        $amount = 0.0
    ) {
        parent::__construct(
            $logger,
            $config,
            $curl,
            $responseFactory
        );

        $this->requestFactory           = $requestFactory;
        $this->paymentRequestFactory    = $paymentRequestFactory;
        $this->orderPayment             = $orderPayment;
        $this->amount                   = $amount;
    }

    /**
     * {@inheritdoc}
     *
     * @return ResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getResponseHandler()
    {
        $responseHandler = $this->responseFactory->create(
            $this->getResponseHandlerType(),
            $this->getRequestId(),
            $this->curl,
            $this->orderPayment
        );

        return $responseHandler;
    }
}
