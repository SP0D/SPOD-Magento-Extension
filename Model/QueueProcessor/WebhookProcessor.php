<?php

namespace Spod\Sync\Model\QueueProcessor;

use Magento\Framework\Event\Manager;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\CrudManager\WebhookManager;
use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Model\ResourceModel\Webhook\Collection;
use Spod\Sync\Model\ResourceModel\Webhook\CollectionFactory;
use Spod\Sync\Model\Webhook;

/**
 * Reads the stored webhooks and processed them
 * by dispatching events, which are picked up by
 * classes for handling each particular case.
 *
 * @package Spod\Sync\Model\QueueProcessor
 */
class WebhookProcessor
{
    private $collectionFactory;
    private $eventManager;
    /**
     * @var SpodLoggerInterface
     */
    private $logger;
    /**
     * @var WebhookManager
     */
    private $webhookManager;
    /**
     * @var WebhookEventRepository
     */
    private $webhookEventRepository;

    public function __construct(
        CollectionFactory $collectionFactory,
        Manager $eventManager,
        SpodLoggerInterface $logger,
        WebhookManager $webhookManager,
        WebhookEventRepository $webhookEventRepository,
        string $name = null
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->webhookManager = $webhookManager;
        $this->webhookEventRepository = $webhookEventRepository;
    }

    public function processPendingWebhookEvents()
    {
        $collection = $this->getPendingEventCollection();

        foreach ($collection as $webhookEvent) {
            $webhookEvent->setStatus(QueueStatus::STATUS_INPROGRESS);
            $this->webhookEventRepository->save($webhookEvent);

            /** @var $webhookEvent Webhook */
            $this->eventManager->dispatch($this->getEventName($webhookEvent), ['webhook_event' => $webhookEvent]);
            $this->logger->logDebug('processing stored webhook event', $webhookEvent->getEventType());
        }
    }

    /**
     * Get the event name, based on the event type.
     * (Article.added becomes spodsync_webhook_event_article_added)
     *
     * @param $webhookEvent
     * @return string
     */
    protected function getEventName(Webhook $webhookEvent): string
    {
        $eventName = strtolower($webhookEvent->getEventType());
        $eventName = str_replace('.', '_', $eventName);
        $eventName = sprintf("spodsync_webhook_event_%s", $eventName);

        return $eventName;
    }

    /**
     * Get collection of webhooks with status pending.
     *
     * @return Collection
     */
    protected function getPendingEventCollection(): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['eq' => QueueStatus::STATUS_PENDING]);

        return $collection;
    }
}
