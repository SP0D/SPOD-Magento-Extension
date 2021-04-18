<?php

namespace Spod\Sync\Model\Decoder;

use Spod\Sync\Api\ResultDecoder;

/**
 * Decoder implementation for the JSON format.
 *
 * @package Spod\Sync\Model\Decoder
 */
class JsonDecoder implements ResultDecoder
{
    public function parsePayload($payload)
    {
        return json_decode($payload);
    }
}
