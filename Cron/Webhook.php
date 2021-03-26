<?php

namespace Spod\Sync\Cron;

use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\QueueProcessor\WebhookProcessor;

class Webhook
{
    /** @var SpodLoggerInterface */
    private $logger;

    /** @var WebhookProcessor */
    private $webhookProcessor;

    public function __construct(
        SpodLoggerInterface $logger,
        WebhookProcessor $webhookProcessor
    ) {
        $this->logger = $logger;
        $this->webhookProcessor = $webhookProcessor;
    }

    public function execute()
    {
        $this->logger->logDebug('Executing Webhook Cronjob');
        $this->webhookProcessor->processPendingWebhookEvents();
    }
}
