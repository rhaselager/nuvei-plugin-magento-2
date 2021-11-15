<?php

namespace Nuvei\Payments\Model\Data;

use Nuvei\Payments\Api\Data\RequestLogInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Nuvei Payments request log data object.
 */
class RequestLog extends AbstractSimpleObject implements RequestLogInterface
{
    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->_get(self::REQUEST_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $id Request id.
     *
     * @return RequestLogInterface
     */
    public function setId($id)
    {
        $this->setData(self::REQUEST_ID, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getRequestId()
    {
        return $this->_get(self::REQUEST_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $id Request id.
     *
     * @return RequestLogInterface
     */
    public function setRequestId($id)
    {
        $this->setData(self::REQUEST_ID, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getParentRequestId()
    {
        return $this->_get(self::PARENT_REQUEST_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $id Request id.
     *
     * @return RequestLogInterface
     */
    public function setParentRequestId($id)
    {
        $this->setData(self::PARENT_REQUEST_ID, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getMethod()
    {
        return $this->_get(self::METHOD);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $method Method.
     *
     * @return RequestLogInterface
     */
    public function setMethod($method)
    {
        $this->setData(self::METHOD, $method);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|array|null
     */
    public function getRequest()
    {
        return $this->_get(self::REQUEST);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $requestData Request data.
     *
     * @return RequestLogInterface
     */
    public function setRequest($requestData)
    {
        $this->setData(self::REQUEST, $requestData);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|array|null
     */
    public function getResponse()
    {
        return $this->_get(self::RESPONSE);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $responseData Response data.
     *
     * @return RequestLogInterface
     */
    public function setResponse($responseData)
    {
        $this->setData(self::RESPONSE, $responseData);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getIncrementId()
    {
        return $this->_get(self::INCREMENT_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $incrementId Increment id.
     *
     * @return RequestLogInterface
     */
    public function setIncrementId($incrementId)
    {
        $this->setData(self::INCREMENT_ID, $incrementId);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $id Customer id.
     *
     * @return RequestLogInterface
     */
    public function setCustomerId($id)
    {
        $this->setData(self::CUSTOMER_ID, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return $this->_get(self::WEBSITE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $websiteId Website id.
     *
     * @return RequestLogInterface
     */
    public function setWebsiteId($websiteId)
    {
        $this->setData(self::WEBSITE_ID, $websiteId);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->_get(self::STORE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $storeId Store id.
     *
     * @return RequestLogInterface
     */
    public function setStoreId($storeId)
    {
        $this->setData(self::STORE_ID, $storeId);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->_get(self::STATUS);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $status Status.
     *
     * @return RequestLogInterface
     */
    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $date Created at date.
     *
     * @return RequestLogInterface
     */
    public function setCreatedAt($date)
    {
        $this->setData(self::CREATED_AT, $date);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->_get(self::UPDATED_AT);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $date Updated at date.
     *
     * @return RequestLogInterface
     */
    public function setUpdatedAt($date)
    {
        $this->setData(self::UPDATED_AT, $date);

        return $this;
    }
}
