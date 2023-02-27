<?php

declare(strict_types=1);

namespace Spod\Sync\Model;

use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\Mapping\WebhookEvent;
use Spod\Sync\Model\ResourceModel\Webhook\Collection;
use Spod\Sync\Model\ResourceModel\Webhook\CollectionFactory;

class PendingWebhookEventsReader implements WebhookEventsReader
{
    private const NUMBER_OF_EVENTS_PER_ITERATION = 10;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function read(): iterable
    {
        $eventTypeItems = [
            [WebhookEvent::EVENT_ARTICLE_INITALSYNC],
            [WebhookEvent::EVENT_ORDER_PROCESSED, WebhookEvent::EVENT_ORDER_CANCELLED, WebhookEvent::EVENT_SHIPMENT_SENT],
            [WebhookEvent::EVENT_ARTICLE_ADDED],
            [WebhookEvent::EVENT_ARTICLE_UPDATED, WebhookEvent::EVENT_ARTICLE_REMOVED]
        ];

        while (!empty($eventTypeItems)) {
            $eventTypes = array_shift($eventTypeItems);
            $collection = $this->createCollection(...$eventTypes);
            foreach ($collection->getIterator() as $item) {
                yield $item;
            }
        }
    }

    private function createCollection(string ...$eventTypes): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('event_type', ['in' => $eventTypes]);
        $collection->addFieldToFilter('status', ['eq' => QueueStatus::STATUS_PENDING]);
        $collection->setOrder('created_at', 'ASC');
        $collection->setPageSize(self::NUMBER_OF_EVENTS_PER_ITERATION);
        return $collection;
    }
}
