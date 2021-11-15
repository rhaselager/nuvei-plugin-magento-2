<?php

namespace Nuvei\Payments\Api\Data;

/**
 * Nuvei Payments request log entity interface.
 */
interface RequestLogInterface
{
    /**
     * Entity data keys.
     */
    const REQUEST_ID = 'request_id';
    const PARENT_REQUEST_ID = 'parent_request_id';
    const METHOD = 'method';
    const REQUEST = 'request';
    const RESPONSE = 'response';
    const INCREMENT_ID = 'increment_id';
    const CUSTOMER_ID = 'customer_id';
    const WEBSITE_ID = 'website_id';
    const STORE_ID = 'store_id';
    const STATUS = 'status';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get request id.
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set request id.
     *
     * @param int $id Request id.
     *
     * @return RequestLogInterface
     */
    public function setId($id);

    /**
     * Get request id.
     *
     * @return int|null
     */
    public function getRequestId();

    /**
     * Set request id.
     *
     * @param int $id Request id.
     *
     * @return RequestLogInterface
     */
    public function setRequestId($id);

    /**
     * Get parent request id.
     *
     * @return int|null
     */
    public function getParentRequestId();

    /**
     * Set parent request id.
     *
     * @param int $id Request id.
     *
     * @return RequestLogInterface
     */
    public function setParentRequestId($id);

    /**
     * Get method.
     *
     * @return string|null
     */
    public function getMethod();

    /**
     * Set method.
     *
     * @param string $method Method.
     *
     * @return RequestLogInterface
     */
    public function setMethod($method);

    /**
     * Get request data.
     *
     * @return string|array|null
     */
    public function getRequest();

    /**
     * Set request data.
     *
     * @param string|array $requestData Request data.
     *
     * @return RequestLogInterface
     */
    public function setRequest($requestData);

    /**
     * Get response data.
     *
     * @return string|array|null
     */
    public function getResponse();

    /**
     * Set response data.
     *
     * @param string|array $responseData Response data.
     *
     * @return RequestLogInterface
     */
    public function setResponse($responseData);

    /**
     * Get increment id.
     *
     * @return string|null
     */
    public function getIncrementId();

    /**
     * Set increment id.
     *
     * @param string $incrementId Increment id.
     *
     * @return RequestLogInterface
     */
    public function setIncrementId($incrementId);

    /**
     * Get customer id.
     *
     * @return int|null
     */
    public function getCustomerId();

    /**
     * Set customer id.
     *
     * @param int $id Customer id.
     *
     * @return RequestLogInterface
     */
    public function setCustomerId($id);

    /**
     * Get website id.
     *
     * @return int
     */
    public function getWebsiteId();

    /**
     * Set website id.
     *
     * @param int $websiteId Website id.
     *
     * @return RequestLogInterface
     */
    public function setWebsiteId($websiteId);

    /**
     * Get store id.
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Set store id.
     *
     * @param int $storeId Store id.
     *
     * @return RequestLogInterface
     */
    public function setStoreId($storeId);

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus();

    /**
     * Set status.
     *
     * @param int $status Status.
     *
     * @return RequestLogInterface
     */
    public function setStatus($status);

    /**
     * Get created at date.
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created at date.
     *
     * @param string $date Created at date.
     *
     * @return RequestLogInterface
     */
    public function setCreatedAt($date);

    /**
     * Get updated at date.
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated at date.
     *
     * @param string $date Updated at date.
     *
     * @return RequestLogInterface
     */
    public function setUpdatedAt($date);
}
