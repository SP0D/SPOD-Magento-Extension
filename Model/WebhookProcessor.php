<?php

namespace Spod\Sync\Model;

use Spod\Sync\Model\ResourceModel\Webhook\CollectionFactory;

class WebhookProcessor
{
    private $collectionFactory;
    private $eventManager;

    public function __construct(
        CollectionFactory $collectionFactory,
        \Magento\Framework\Event\Manager $eventManager,
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
     * @return ResourceModel\Webhook\Collection
     */
    protected function getPendingEventCollection(): ResourceModel\Webhook\Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['eq' => WebhookStatus::WEBHOOK_STATUS_PENDING]);

        return $collection;
    }
}
