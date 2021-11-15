<?php

namespace Nuvei\Payments\Model\Plugin\Service\CreditmemoService;

use Nuvei\Payments\Api\Data\RequestLogInterface;
use Nuvei\Payments\Model\RequestLogFactory;
use Nuvei\Payments\Model\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry as CoreRegistry;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Service\CreditmemoService;

/**
 * Nuvei Payments credit memo service plugin model.
 */
class Plugin
{
    /**
     * @var CoreRegistry
     */
    private $coreRegistry;

    /**
     * @var RequestLogFactory
     */
    private $requestLogFactory;

    /**
     * Object constructor.
     *
     * @param CoreRegistry                  $coreRegistry
     * @param RequestLogFactory $requestLogFactory
     */
    public function __construct(
        CoreRegistry $coreRegistry,
        RequestLogFactory $requestLogFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->requestLogFactory = $requestLogFactory;
    }

    /**
     * @param CreditmemoService $creditmemoService
     * @param \Closure          $closure
     * @param Creditmemo        $creditmemo
     * @param bool              $offlineRequested
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundRefund(
        CreditmemoService $creditmemoService,
        \Closure $closure,
        Creditmemo $creditmemo,
        $offlineRequested
    ) {
        try {
            $closure($creditmemo, $offlineRequested);
        } catch (LocalizedException $e) {
            /** @var RequestLogInterface $currentRequestLog */
            $currentRequestLog = $this->coreRegistry->registry(Logger::CURRENT_REQUEST_LOG);
            if ($currentRequestLog !== null) {
                $requestLog = $this->requestLogFactory->create();
                $requestLog->updateData($currentRequestLog);
                $requestLog->forceSave();
            }

            throw $e;
        }
    }
}
