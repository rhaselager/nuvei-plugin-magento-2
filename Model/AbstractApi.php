<?php

namespace Nuvei\Payments\Model;

use Nuvei\Payments\Model\Logger as Logger;

/**
 * Nuvei Payments abstract api model.
 */
abstract class AbstractApi
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Object initialization.
     *
     * @param Logger $logger
     * @param Config           $config
     */
    public function __construct(
        Logger $logger,
        Config $config
    ) {
        $this->logger = $logger;
        $this->config = $config;
    }
}
