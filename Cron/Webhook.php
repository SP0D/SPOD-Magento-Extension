<?php

namespace Spod\Sync\Cron;

use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\QueueProcessor\WebhookProcessor;

/**
 * Cronjob for webhooks. Executes stored and
 * yet unprocessed webhook events.
 *
 * @package Spod\Sync\Cron
 */
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
        $this->logger->logDebug('[Cron]: Executing Webhooks');
        $this->webhookProcessor->processPendingWebhookEvents();
    }
}
