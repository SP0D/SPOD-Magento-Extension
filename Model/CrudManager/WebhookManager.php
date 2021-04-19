<?php

namespace Spod\Sync\Model\CrudManager;

use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Model\WebhookFactory;

/**
 * Stores received webhook requests in the
 * the database.
 *
 * @package Spod\Sync\Model\CrudManager
 */
class WebhookManager
{
    /**
     * @var WebhookFactory
     */
    private $webhookFactory;

    public function __construct(
        WebhookFactory $webhookFactory,
        WebhookEventRepository $webhookEventRepository
    ) {
        $this->webhookEventRepository = $webhookEventRepository;
        $this->webhookFactory = $webhookFactory;
    }

    /**
     * @param $eventType
     * @param $rawJson
     * @throws \Exception
     */
    public function saveWebhookEvent($eventType, $rawJson): void
    {
        $webhook = $this->webhookFactory->create();
        $webhook->setEventType($eventType);
        $webhook->setStatus(QueueStatus::STATUS_PENDING);
        $webhook->setPayload($rawJson);
        $this->webhookEventRepository->save($webhook);
    }
}
