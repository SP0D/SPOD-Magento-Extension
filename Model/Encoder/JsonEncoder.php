<?php

namespace Spod\Sync\Model\Encoder;

use Spod\Sync\Api\PayloadEncoder;

class JsonEncoder implements PayloadEncoder
{
    public function encodePayload($payload)
    {
        return json_encode($payload);
    }
}
