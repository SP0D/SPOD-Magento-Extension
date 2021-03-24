<?php

namespace Spod\Sync\Model\QueueProcessor;

use Magento\Framework\Event\Manager;
use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\ResourceModel\Webhook\Collection;
use Spod\Sync\Model\ResourceModel\Webhook\CollectionFactory;
use Spod\Sync\Model\Webhook;

class WebhookProcessor
{
    private $collectionFactory;
    private $eventManager;

    public function __construct(
        CollectionFactory $collectionFactory,
        Manager $eventManager,
        string $name = null
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->eventManager = $eventManager;
    }

    public function processPendingWebhookEvents()
    {
        $collection = $this->getPendingEventCollection();

        foreach ($collection as $webhookEvent) {
            /** @var $webhook Webhook */
            $this->eventManager->dispatch($this->getEventName($webhookEvent), ['webhook_event' => $webhookEvent]);
        }
    }

    /**
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
     * @return Collection
     */
    protected function getPendingEventCollection(): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['eq' => QueueStatus::STATUS_PENDING]);

        return $collection;
    }
}
