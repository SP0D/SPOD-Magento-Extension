<?php

namespace Spod\Sync\Model;

/**
 * Represents and API result and contains
 * the payload as well as the HTTP code.
 *
 * @package Spod\Sync\Model
 */
class ApiResult
{
    private $httpCode;
    private $payload;

    /**
     * @return mixed
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * @param mixed $httpCode
     */
    public function setHttpCode($httpCode): void
    {
        $this->httpCode = $httpCode;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param mixed $payload
     */
    public function setPayload($payload): void
    {
        $this->payload = $payload;
    }
}
