<?php

namespace Nuvei\Checkout\Model;

use Nuvei\Checkout\Api\Data\RequestLogInterface;
use Nuvei\Checkout\Api\Data\RequestLogInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Nuvei Checkout request log model.
 */
class RequestLog extends AbstractModel
{
    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'request_log';

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var RequestLogInterfaceFactory
     */
    private $requestLogFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * RequestLog constructor.
     *
     * @param Context                    $context
     * @param Registry                   $registry
     * @param DateTime                   $dateTime
     * @param DataObjectProcessor        $dataObjectProcessor
     * @param RequestLogInterfaceFactory $requestLogFactory
     * @param DataObjectHelper           $dataObjectHelper
     * @param AbstractResource|null      $resource
     * @param AbstractDb|null            $resourceCollection
     * @param array                      $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DateTime $dateTime,
        DataObjectProcessor $dataObjectProcessor,
        RequestLogInterfaceFactory $requestLogFactory,
        DataObjectHelper $dataObjectHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->dateTime = $dateTime;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->requestLogFactory = $requestLogFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * Model construct that should be used for object initialization.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_init(\Nuvei\Checkout\Model\ResourceModel\RequestLog::class);
    }

    /**
     * Prepare some subaccount data before save.
     *
     * @return AbstractModel
     */
    public function beforeSave()
    {
        $this->setUpdatedAt($this->dateTime->gmtDate());

        if ($this->isObjectNew()) {
            $this->setCreatedAt($this->getUpdatedAt());
        }

        return parent::beforeSave();
    }

    /**
     * Update request log data.
     *
     * @param RequestLogInterface $requestLogData Request log data object.
     *
     * @return RequestLog
     */
    public function updateData(RequestLogInterface $requestLogData)
    {
        /** @var array $requestLogDataArray */
        $requestLogDataArray = $this->dataObjectProcessor
            ->buildOutputDataArray(
                $requestLogData,
                RequestLogInterface::class
            );

        foreach ($requestLogDataArray as $key => $value) {
            $this->setDataUsingMethod($key, $value);
        }

        return $this;
    }

    /**
     * Retrieve request log data object.
     *
     * @return RequestLogInterface
     */
    public function getDataModel()
    {
        $requestLogData = $this->getData();
        $requestLogDataObject = $this->requestLogFactory->create();

        $this->dataObjectHelper->populateWithArray(
            $requestLogDataObject,
            $requestLogData,
            RequestLogInterface::class
        );

        $requestLogDataObject->setId($this->getId());

        return $requestLogDataObject;
    }

    /**
     * @return RequestLog
     * @throws \Exception
     */
    public function forceSave()
    {
        $this
            ->setId(null)
            ->save();

        return $this;
    }
}
