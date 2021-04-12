<?php

namespace Spod\Sync\Api;

interface SpodLoggerInterface
{
    public function logDebug($message, $event = 'debug');
    public function logError($event, $message, $payload = "");
}
