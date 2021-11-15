<?php

namespace Nuvei\Payments\Model\Response;

use Nuvei\Payments\Lib\Http\Client\Curl;
use Nuvei\Payments\Model\AbstractResponse;
use Nuvei\Payments\Model\Config;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Nuvei\Payments\Model\Logger;

/**
 * Nuvei Payments abstract payment response model.
 */
abstract class AbstractPayment extends AbstractResponse
{
    /**
     * @var OrderPayment
     */
    protected $orderPayment;

    /**
     * AbstractPayment constructor.
     *
     * @param Logger            $logger
     * @param Config            $config
     * @param int               $requestId
     * @param Curl              $curl
     * @param OrderPayment|null $orderPayment
     */
    public function __construct(
        Logger $logger,
        Config $config,
        $requestId,
        Curl $curl,
        OrderPayment $orderPayment
    ) {
        parent::__construct(
            $logger,
            $config,
            $requestId,
            $curl
        );

        $this->orderPayment = $orderPayment;
    }

    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        parent::process();

        $this
            ->processResponseData()
            ->updateTransaction();

        return $this;
    }

    /**
     * @return AbstractPayment
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateTransaction()
    {
        $body = $this->getBody();
        $transactionKeys = $this->getRequiredResponseDataKeys();

        $transactionInformation = [];
        foreach ($transactionKeys as $transactionKey) {
            if (!isset($body[$transactionKey])) {
                continue;
            }

            $transactionInformation[$transactionKey] = $body[$transactionKey];
        }
        ksort($transactionInformation);

        return $this;
    }
}
