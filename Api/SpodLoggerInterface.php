<?php

namespace Spod\Sync\Api;

/**
 * Interface SpodLoggerInterface
 *
 * Used for logging implementations.
 *
 * @package Spod\Sync\Api
 */
interface SpodLoggerInterface
{
    public function logDebug($message, $event = 'debug');
    public function logError($event, $message, $payload = "");
}
