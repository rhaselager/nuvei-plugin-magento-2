<?php

namespace Nuvei\Checkout\Model\ResourceModel;

use Nuvei\Checkout\Api\Data\RequestLogInterface;
use Nuvei\Checkout\Api\RequestLogRepositoryInterface;
use Nuvei\Checkout\Model\RequestLogFactory;
use Magento\Framework\Event\ManagerInterface;

/**
 * Nuvei Checkout request log repository model.
 */
class RequestLogRepository implements RequestLogRepositoryInterface
{
    /**
     * Request log factory object.
     *
     * @var RequestLogFactory
     */
    private $requestLogFactory;

    /**
     * Event manager object.
     *
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * RequestLogRepository constructor.
     *
     * @param RequestLogFactory $requestLogFactory
     * @param ManagerInterface  $eventManager
     */
    public function __construct(
        RequestLogFactory $requestLogFactory,
        ManagerInterface $eventManager
    ) {
        $this->requestLogFactory = $requestLogFactory;
        $this->eventManager = $eventManager;
    }

    /**
     * {@inheritdoc}
     *
     * @param RequestLogInterface $requestLogData Request log data object.
     *
     * @return RequestLogInterface
     * @throws \Exception
     */
    public function save(RequestLogInterface $requestLogData)
    {
        $requestLog = $this->requestLogFactory->create();
        if ($requestLogData->getId()) {
            $requestLog->load($requestLogData->getId());
        }

        $requestLog
            ->updateData($requestLogData)
            ->save();
        $requestLogData = $requestLog->getDataModel();

        $this->eventManager->dispatch(
            'requestlog_save_after_data_object',
            [
                'requestlog_data_object' => $requestLogData,
            ]
        );

        return $requestLogData;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $requestId Request id.
     *
     * @return RequestLogInterface
     */
    public function getById($requestId)
    {
        $requestLog = $this->requestLogFactory->create();
        $requestLog->load($requestId);

        return $requestLog->getDataModel();
    }

    /**
     * {@inheritdoc}
     *
     * @param RequestLogInterface $requestLogData Request log data object.
     *
     * @return bool
     * @throws \Exception
     */
    public function delete(RequestLogInterface $requestLogData)
    {
        return $this->deleteById($requestLogData->getId());
    }

    /**
     * {@inheritdoc}
     *
     * @param int $requestId Request id.
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteById($requestId)
    {
        $requestLog = $this->requestLogFactory->create();
        $requestLog->load($requestId);
        $requestLog->delete();

        return true;
    }
}
