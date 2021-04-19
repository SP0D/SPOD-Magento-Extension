<?php

namespace Spod\Sync\Model;

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
}
