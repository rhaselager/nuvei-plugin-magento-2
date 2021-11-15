<?php

namespace Nuvei\Payments\Model\Request;

use Nuvei\Payments\Model\AbstractRequest;
use Nuvei\Payments\Model\AbstractResponse;
use Nuvei\Payments\Model\RequestInterface;

class DeleteUPO extends AbstractRequest implements RequestInterface
{
    protected $config;
    
    private $upo_id;
    
    /**
     * @param Logger            $logger
     * @param Config            $config
     * @param Curl              $curl
     * @param ResponseFactory   $responseFactory
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
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws PaymentException
     */
    public function process()
    {
        $resp = $this->sendRequest(true);
        
        if (!empty($resp['status'])) {
            return strtolower($resp['status']);
        }
        
        return 'error';
    }
    
    public function setUpoId($upo_id)
    {
        $this->upo_id = $upo_id;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getParams()
    {
        $billing_address    = $this->config->getQuoteBillingAddress();
        $email              = $billing_address['email'] ?: $this->config->getUserEmail(true);
        
        $params = array_merge_recursive(
            parent::getParams(),
            [
                'userTokenId'            => $email, // logged user email
                'userPaymentOptionId'    => $this->upo_id,
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
            'userTokenId',
            'clientRequestId',
            'userPaymentOptionId',
            'timeStamp',
        ];
    }
    
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return self::DELETE_UPOS_METHOD;
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
}
