<?php

namespace Spod\Sync\Api;

/**
 * Interface ResultDecoder
 *
 * Used to generalize the API format, makes
 * it possible to implement XML parsers later on.
 *
 * @package Spod\Sync\Api
 */
interface ResultDecoder
{
    public function parsePayload($payload);
}
