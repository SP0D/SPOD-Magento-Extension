<?php

namespace Spod\Sync\Api;

/**
 * Interface PayloadEncoder
 *
 * Used to generalize the API format, makes
 * it possible to implement XML parsers later on.
 *
 * @package Spod\Sync\Api
 */
interface PayloadEncoder
{
    public function encodePayload($payload);
}
