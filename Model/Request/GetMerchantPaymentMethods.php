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
     * @param Config           $moduleConfig
     * @param Curl             $curl
     * @param ResponseFactory  $responseFactory
     * @param Factory          $requestFactory
     */
    public function __construct(
        \Nuvei\Checkout\Model\Logger $logger,
        Config $moduleConfig,
        Curl $curl,
        ResponseFactory $responseFactory,
        RequestFactory $requestFactory,
        \Magento\Store\Api\Data\StoreInterface $store,
        \Magento\Checkout\Model\Cart $cart
    ) {
        parent::__construct(
            $logger,
            $moduleConfig,
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
        if (!$this->config->isActive()) {
            $this->config->createLog('GetMerchantPaymentMethods Error - '
                . 'Nuvei payments module is not active at the moment!');
            return [];
        }
        if (empty($this->config->getMerchantId())) {
            $this->config->createLog('GetMerchantPaymentMethods Error - merchantId is empty!');
            return [];
        }
        if (empty($this->config->getMerchantSiteId())) {
            $this->config->createLog('GetMerchantPaymentMethods Error - merchantSiteId is empty!');
            return [];
        }
        if (empty($this->config->getMerchantSecretKey())) {
            $this->config->createLog('GetMerchantPaymentMethods Error - merchant secret key is empty!');
            return [];
        }
        if (empty($this->config->getHash())) {
            $this->config->createLog('GetMerchantPaymentMethods Error - merchant hash is empty!');
            return [];
        }
        
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
        $tokenRequest   = $this->requestFactory->create(AbstractRequest::GET_SESSION_TOKEN);
        $tokenResponse  = $tokenRequest->process();
        
        $languageCode = 'en';
        if ($this->store && $this->store->getLocaleCode()) {
            $languageCode = $this->store->getLocaleCode();
        }
        
        $country_code = isset($this->billing_address['countryId']) ? $this->billing_address['countryId'] : '';
        if (empty($country_code)) {
            $country_code = $this->config->getQuoteCountryCode();
        }
        
        $params = array_merge_recursive(
            parent::getParams(),
            [
                'sessionToken'  => !empty($tokenResponse['sessionToken']) ? $tokenResponse['sessionToken'] : '',
                "currencyCode"  => $this->config->getStoreCurrency(),
                "countryCode"   => $country_code,
                "languageCode"  => $languageCode,
            ]
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
            'clientRequestId',
            'timeStamp',
        ];
    }
}
