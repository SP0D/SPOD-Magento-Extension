<?php

namespace Spod\Sync\Model\Logger;

use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Model\ResourceModel\SpodLog as SpodLogResource;
use Spod\Sync\Model\SpodLog;
use Spod\Sync\Model\SpodLogFactory;

class BackendLog implements SpodLoggerInterface
{
    /** @var ConfigHelper */
    private $configHelper;
    /**
     * @var SpodLogResource
     */
    private $spodLogResource;
    /**
     * @var SpodLogFactory
     */
    private $spodLogFactory;

    public function __construct(
        ConfigHelper $configHelper,
        SpodLogFactory $spodLogFactory,
        SpodLogResource $spodLogResource
    ) {
        $this->configHelper = $configHelper;
        $this->spodLogFactory = $spodLogFactory;
        $this->spodLogResource = $spodLogResource;
    }

    public function logDebug($message, $event = 'debug')
    {
        if ($this->configHelper->debugLogging()) {
            /** @var SpodLog $log */
            $log = $this->spodLogFactory->create();
            $log->setEvent($event);
            $log->setMessage($message);
            $log->setPayload('');
            $log->setCreatedAt(new \DateTime());
            $this->spodLogResource->save($log);
        }
    }

    public function logError($event, $message, $payload = "")
    {
        $log = $this->spodLogFactory->create();
        $log->setEvent($event);
        $log->setMessage($message);
        $log->setPayload($payload);
        $log->setCreatedAt(new \DateTime());
        $this->spodLogResource->save($log);
    }
}
