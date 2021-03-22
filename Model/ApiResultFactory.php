<?php

namespace Spod\Sync\Model;

class ApiResultFactory
{
    public function create(): ApiResult
    {
        return new ApiResult();
    }
}
