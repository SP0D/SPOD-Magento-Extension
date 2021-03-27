<?php

namespace Spod\Sync\Model\Logger;

use Psr\Log\LoggerInterface;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\ConfigHelper;

class PsrLog implements SpodLoggerInterface
{
    const LOGPREFIX = '[SPODSYNC]';

    /** @var ConfigHelper */
    private $configHelper;
    /** @var LoggerInterface  */
    private $logger;

    public function __construct(
        ConfigHelper $configHelper,
        LoggerInterface $logger
    ) {
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    public function logDebug($message)
    {
        if ($this->configHelper->debugLogging()) {
            $this->logger->debug($this->getPrefixedLogMsg($message));
        }
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
