<?php

namespace Nuvei\Payments\Model\Request;

use Nuvei\Payments\Model\AbstractRequest;
use Nuvei\Payments\Model\AbstractResponse;
use Nuvei\Payments\Model\RequestInterface;

/**
 * Nuvei Payments get user payment options request model.
 */
class GetUserUPOs extends AbstractRequest implements RequestInterface
{
    protected $config;
    
    /**
     * @param Logger           $logger
     * @param Config           $config
     * @param Curl             $curl
     * @param ResponseFactory  $responseFactory
     */
    public function __construct(
        \Nuvei\Payments\Model\Logger $logger,
        \Nuvei\Payments\Model\Config $config,
        \Nuvei\Payments\Lib\Http\Client\Curl $curl,
        \Nuvei\Payments\Model\Response\Factory $responseFactory
    ) {
        parent::__construct(
            $logger,
            $config,
            $curl,
            $responseFactory
        );

        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return self::GET_UPOS_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractResponse::GET_UPOS_HANDLER;
    }

    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws PaymentException
     */
    public function process()
    {
        $res = $this->sendRequest(true, true);
        $pms = [];
        
        if (!empty($res['paymentMethods']) && is_array($res['paymentMethods'])) {
            foreach ($res['paymentMethods'] as $method) {
                if (!empty($method['expiryDate']) && date('Ymd') > $method['expiryDate']) {
                    continue;
                }

                if (empty($method['upoStatus']) || $method['upoStatus'] !== 'enabled') {
                    continue;
                }

                $pms[] = $method;
            }
        }
        
        return $pms;
    }
    
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getParams()
    {
        $billing_address    = $this->config->getQuoteBillingAddress();
        $email                = $billing_address['email'] ?: $this->config->getUserEmail(true);
        
        $params = [
            'userTokenId' => $email, // logged user email
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
            'userTokenId',
            'clientRequestId',
            'timeStamp',
        ];
    }
}
