<?php

namespace Spod\Sync\Model\Decoder;

use Spod\Sync\Api\ResultDecoder;

class JsonDecoder implements ResultDecoder
{
    public function parsePayload($payload)
    {
        return json_decode($payload);
    }
}
