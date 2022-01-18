<?php

namespace Nuvei\Checkout\Model;

use Nuvei\Checkout\Api\Data\RequestLogInterface;
use Nuvei\Checkout\Api\RequestLogRepositoryInterface;
use Nuvei\Checkout\Model\Data\RequestLogFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Registry as CoreRegistry;

/**
 * Nuvei Checkout logger model.
 */
class Logger extends \Monolog\Logger
{
    const CURRENT_REQUEST_LOG = 'current_request_log';

    /**
     * @var Config
     */
    private $moduleConfig;

    /**
     * @var RequestLogFactory
     */
    private $requestLogFactory;

    /**
     * @var RequestLogRepositoryInterface
     */
    private $requestLogRepository;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PrivateDataKeysProvider
     */
    private $privateDataKeysProvider;

    /**
     * @var CoreRegistry
     */
    private $coreRegistry;

    /**
     * Logger constructor.
     *
     * @param Config                        $moduleConfig
     * @param RequestLogFactory             $requestLogFactory
     * @param RequestLogRepositoryInterface $requestLogRepository
     * @param DataObjectHelper              $dataObjectHelper
     * @param CustomerSession               $customerSession
     * @param StoreManagerInterface         $storeManager
     * @param PrivateDataKeysProvider       $privateDataKeysProvider
     * @param CoreRegistry                  $coreRegistry
     * @param string                        $name
     * @param array                         $handlers
     * @param array                         $processors
     */
    public function __construct(
        Config $moduleConfig,
        RequestLogFactory $requestLogFactory,
        RequestLogRepositoryInterface $requestLogRepository,
        DataObjectHelper $dataObjectHelper,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        PrivateDataKeysProvider $privateDataKeysProvider,
        CoreRegistry $coreRegistry,
        $name,
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct(
            $name,
            $handlers,
            $processors
        );

        $this->moduleConfig             = $moduleConfig;
        $this->requestLogFactory        = $requestLogFactory;
        $this->requestLogRepository     = $requestLogRepository;
        $this->dataObjectHelper         = $dataObjectHelper;
        $this->customerSession          = $customerSession;
        $this->storeManager             = $storeManager;
        $this->privateDataKeysProvider  = $privateDataKeysProvider;
        $this->coreRegistry             = $coreRegistry;
    }

    /**
     * @param array $requestDataArray
     *
     * @return RequestLogInterface
     * @throws \RuntimeException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createRequest(array $requestDataArray)
    {
        $requestDataArray = $this->filterPrivateData($requestDataArray);

        $requestDataArray = array_merge_recursive(
            $this->getDefaultData(),
            $requestDataArray
        );

        $requestLog = $this->requestLogFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $requestLog,
            $requestDataArray,
            RequestLogInterface::class
        );

        $requestLog = $this->requestLogRepository->save($requestLog);
        $this->coreRegistry->unregister(self::CURRENT_REQUEST_LOG);
        $this->coreRegistry->register(self::CURRENT_REQUEST_LOG, $requestLog);

        return $requestLog;
    }

    /**
     * @param int   $requestId
     * @param array $requestDataArray
     *
     * @return RequestLogInterface
     * @throws \RuntimeException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateRequest($requestId, array $requestDataArray)
    {
        $requestDataArray = $this->filterPrivateData($requestDataArray);

        if ((!empty($requestDataArray['request']) || !empty($requestDataArray['response']))
            && $this->moduleConfig->isDebugEnabled() === true
        ) {
            $string = print_r(json_encode($requestDataArray), true) . "\r\n\r\n";
            $this->debug($string);
        }

        $requestLogData = $this->requestLogRepository->getById($requestId);
        $this->dataObjectHelper->populateWithArray(
            $requestLogData,
            $requestDataArray,
            RequestLogInterface::class
        );

        $requestLog = $this->requestLogRepository->save($requestLogData);
        $this->coreRegistry->unregister(self::CURRENT_REQUEST_LOG);
        $this->coreRegistry->register(self::CURRENT_REQUEST_LOG, $requestLog);

        return $requestLogData;
    }

    /**
     * @param int $requestId
     *
     * @return RequestLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRequest($requestId)
    {
        return $this->requestLogRepository->getById($requestId);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getDefaultData()
    {
        return [
            'customer_id'   => $this->customerSession->getCustomerId(),
            'website_id'    => $this->storeManager->getWebsite()->getId(),
            'store_id'      => $this->storeManager->getStore()->getId(),
        ];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function filterPrivateData(array $data)
    {
        if (!empty($data['request'])) {
            $this->filterPrivateDataArray($data['request']);
            $data['request'] = json_encode($data['request']);
        }
        if (!empty($data['response'])) {
            $this->filterPrivateDataArray($data['response']);
            $data['response'] = json_encode($data['response']);
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function filterPrivateDataArray(array &$data)
    {
        $privateKeys = $this->privateDataKeysProvider->getConfig();
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $this->filterPrivateDataArray($value);
                continue;
            }

            if (in_array($key, $privateKeys, true)) {
                switch ($key) {
                    case 'cardNumber':
                        $value = 'xxxx-' . substr($value, -4);
                        break;
                    default:
                        $value = '***';
                }
            }
        }

        return $data;
    }
}
