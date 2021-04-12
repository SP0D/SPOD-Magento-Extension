<?php

namespace Spod\Sync\Api;

interface SpodLoggerInterface
{
    public function logDebug($message);
    public function logError($event, $message, $payload = "");
}
