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
    /** @var int */
    private $httpCode;

    /** @var mixed */
    private $payload;

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    public function setHttpCode(int $httpCode): void
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
