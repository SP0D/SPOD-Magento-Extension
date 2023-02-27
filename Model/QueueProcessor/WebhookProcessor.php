<?php

namespace Spod\Sync\Model\QueueProcessor;

use Exception;
use Magento\Framework\Event\Manager;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Model\WebhookEventsReader;

/**
 * Reads the stored webhooks and processed them
 * by dispatching events, which are picked up by
 * classes for handling each particular case.
 *
 * @package Spod\Sync\Model\QueueProcessor
 */
class WebhookProcessor
{
    private WebhookEventsReader $webhookEventsReader;

    /**
     * @var Manager
     */
    private $eventManager;

    /**
     * @var SpodLoggerInterface
     */
    private $logger;

    /**
     * @var WebhookEventRepository
     */
    private $webhookEventRepository;

    /** @var StatusHelper */
    private $statusHelper;

    public function __construct(
        WebhookEventsReader $webhookEventsReader,
        Manager $eventManager,
        SpodLoggerInterface $logger,
        WebhookEventRepository $webhookEventRepository,
        StatusHelper $statusHelper
    ) {
        $this->webhookEventsReader = $webhookEventsReader;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->webhookEventRepository = $webhookEventRepository;
        $this->statusHelper = $statusHelper;
    }

    public function processPendingWebhookEvents()
    {
        foreach ($this->webhookEventsReader->read() as $webhookEvent) {
            try {
                $webhookEvent->setStatus(QueueStatus::STATUS_INPROGRESS);
                $this->webhookEventRepository->save($webhookEvent);

                $this->logger->logDebug('processing stored webhook event', $webhookEvent->getEventType());
                $this->eventManager->dispatch($this->getEventName($webhookEvent), ['webhook_event' => $webhookEvent]);

                $this->statusHelper->setLastsyncDate();
                $webhookEvent->setStatus(QueueStatus::STATUS_PROCESSED);
                $webhookEvent->setProcessedAt(new \DateTimeImmutable());
                $this->webhookEventRepository->save($webhookEvent);
            } catch (Exception $e) {
                $webhookEvent->setStatus(QueueStatus::STATUS_ERROR);
                $webhookEvent->setProcessedAt(new \DateTimeImmutable());
                $this->webhookEventRepository->save($webhookEvent);
                $this->logger->logError($webhookEvent->getEventType(), $e->getMessage());
            }
        }
    }

    /**
     * Get the event name, based on the event type.
     * (Article.added becomes spodsync_webhook_event_article_added)
     *
     * @param Webhook $webhookEvent
     * @return string
     */
    protected function getEventName(Webhook $webhookEvent): string
    {
        $eventName = strtolower($webhookEvent->getEventType());
        $eventName = str_replace('.', '_', $eventName);
        $eventName = sprintf("spodsync_webhook_event_%s", $eventName);

        return $eventName;
    }
}
