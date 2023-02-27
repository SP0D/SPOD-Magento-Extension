<?php

declare(strict_types=1);

namespace Spod\Sync\Model;

use Psr\Http\Message\ResponseInterface;

/**
 * Factory class for ApiResult objects.
 *
 * @package Spod\Sync\Model
 */
class ApiResultFactory
{
    public function create(): ApiResult
    {
        return new ApiResult();
    }

    public function createFromResponse(ResponseInterface $response): ApiResult
    {
        $result = new ApiResult();
        $result->setHttpCode($response->getStatusCode());
        $result->setPayload(
            json_decode($response->getBody()->getContents(), false)
        );
        return $result;
    }
}
