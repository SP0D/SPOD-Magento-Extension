<?php

namespace Spod\Sync\Model\Logger;

use Psr\Log\LoggerInterface;
use Spod\Sync\Api\SpodLoggerInterface;

class PsrLog implements SpodLoggerInterface
{
    const LOGPREFIX = '[SPODSYNC]';

    private $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function logDebug($message)
    {
        $this->logger->debug($this->getPrefixedLogMsg($message));
    }

    public function logError($message)
    {
        $this->logger->error($this->getPrefixedLogMsg($message));
    }

    /**
     * @param $message
     * @return string
     */
    protected function getPrefixedLogMsg($message): string
    {
        return sprintf("%s: %s", self::LOGPREFIX, $message);
    }
}
