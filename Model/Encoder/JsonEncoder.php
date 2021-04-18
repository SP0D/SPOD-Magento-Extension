<?php

namespace Spod\Sync\Model\Encoder;

use Spod\Sync\Api\PayloadEncoder;

/**
 * Encoder implementation for the JSON format.
 *
 * @package Spod\Sync\Model\Decoder
 */
class JsonEncoder implements PayloadEncoder
{
    public function encodePayload($payload)
    {
        return json_encode($payload);
    }
}
