<?php

namespace Nuvei\Checkout\Model\Request;

//use Magento\Sales\Model\Order\Payment as OrderPayment;
//use Nuvei\Checkout\Lib\Http\Client\Curl;
//use Nuvei\Checkout\Model\AbstractRequest;
//use Nuvei\Checkout\Model\Config;
//use Nuvei\Checkout\Model\Logger as Logger;
//use Nuvei\Checkout\Model\Request\Factory as RequestFactory;
//use Nuvei\Checkout\Model\Request\Payment\Factory as PaymentRequestFactory;
//use Nuvei\Checkout\Model\Response\Factory as ResponseFactory;
//use Nuvei\Checkout\Model\ResponseInterface;

/**
 * Nuvei Checkout abstract payment request model.
 */
abstract class AbstractPayment extends \Nuvei\Checkout\Model\AbstractRequest
{
    /**
     * @var RequestFactory
     */
//    protected $requestFactory;

    /**
     * @var PaymentRequestFactory
     */
//    protected $paymentRequestFactory;

    /**
     * @var OrderPayment
     */
    protected $orderPayment;

    /**
     * @var float
     */
//    protected $amount;
    
//    protected $httpRequest;

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
//        Logger $logger,
        \Nuvei\Checkout\Model\Config $config,
        \Nuvei\Checkout\Lib\Http\Client\Curl $curl,
//        RequestFactory $requestFactory,
//        PaymentRequestFactory $paymentRequestFactory,
        \Nuvei\Checkout\Model\Response\Factory $responseFactory,
        \Magento\Sales\Model\Order\Payment $orderPayment,
        \Nuvei\Checkout\Model\ReaderWriter $readerWriter
//        \Magento\Framework\App\Request\Http $httpRequest
//        $amount = 0.0
    ) {
        parent::__construct(
//            $logger,
            $config,
            $curl,
            $responseFactory,
            $readerWriter
        );

//        $this->requestFactory           = $requestFactory;
//        $this->paymentRequestFactory    = $paymentRequestFactory;
        $this->orderPayment             = $orderPayment;
//        $this->amount                   = $amount;
//        $this->httpRequest              = $httpRequest;
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
