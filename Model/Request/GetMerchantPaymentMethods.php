<?php

namespace Nuvei\Checkout\Model\Request;

use Nuvei\Checkout\Lib\Http\Client\Curl;
use Nuvei\Checkout\Model\AbstractRequest;
use Nuvei\Checkout\Model\AbstractResponse;
use Nuvei\Checkout\Model\Config;
use Nuvei\Checkout\Model\Request\Factory as RequestFactory;
use Nuvei\Checkout\Model\RequestInterface;
use Nuvei\Checkout\Model\Response\Factory as ResponseFactory;

/**
 * Nuvei Checkout get merchant payment methods request model.
 */
class GetMerchantPaymentMethods extends AbstractRequest implements RequestInterface
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var string
     */
    protected $countryCode;
    
    protected $store;
    
    private $billing_address;
    private $cart;
    
    /**
     * @param Logger $logger
     * @param Config           $config
     * @param Curl             $curl
     * @param ResponseFactory  $responseFactory
     * @param Factory          $requestFactory
     */
    public function __construct(
        \Nuvei\Checkout\Model\Logger $logger,
        Config $config,
        Curl $curl,
        ResponseFactory $responseFactory,
        RequestFactory $requestFactory,
        \Magento\Store\Api\Data\StoreInterface $store,
        \Magento\Checkout\Model\Cart $cart
    ) {
        parent::__construct(
            $logger,
            $config,
            $curl,
            $responseFactory
        );

        $this->requestFactory   = $requestFactory;
        $this->store            = $store;
        $this->cart             = $cart;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return self::GET_MERCHANT_PAYMENT_METHODS_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractResponse::GET_MERCHANT_PAYMENT_METHODS_HANDLER;
    }

    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws PaymentException
     */
    public function process()
    {
        $this->sendRequest();

        return $this
            ->getResponseHandler()
            ->process();
    }
    
    public function setBillingAddress($billing_address)
    {
        $this->billing_address = json_decode($billing_address, true);
        
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getParams()
    {
        $tokenRequest = $this->requestFactory->create(AbstractRequest::OPEN_ORDER_METHOD);
        
        $tokenResponse = $tokenRequest
            ->setBillingAddress($this->billing_address)
            ->process();
        
        $languageCode = 'en';
        if ($this->store && $this->store->getLocaleCode()) {
            $languageCode = $this->store->getLocaleCode();
        }
        
        $country_code = isset($this->billing_address['countryId']) ? $this->billing_address['countryId'] : '';
        if (empty($country_code)) {
            $country_code = $this->config->getQuoteCountryCode();
        }
        
        $currencyCode = $this->config->getQuoteBaseCurrency();
        if ((empty($currencyCode) || null === $currencyCode)
            && $this->cart
        ) {
            $currencyCode = empty($this->cart->getQuote()->getOrderCurrencyCode())
                ? $this->cart->getQuote()->getStoreCurrencyCode() : $this->cart->getQuote()->getOrderCurrencyCode();
        }
        
        $params = [
            'sessionToken'  => $tokenResponse->sessionToken,
            "currencyCode"  => $currencyCode,
            "countryCode"   => $country_code,
            "languageCode"  => $languageCode,
        ];

        $params = array_merge_recursive(parent::getParams(), $params);
        
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
            'clientRequestId',
            'timeStamp',
        ];
    }
}
