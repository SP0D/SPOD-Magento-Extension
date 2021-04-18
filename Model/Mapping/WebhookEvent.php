<?php

namespace Spod\Sync\Model\Mapping;

/**
 * Class which maps the API webhooks event strings
 * to a shared constant that can be accessed throughout
 * the extension.
 *
 * @package Spod\Sync\Model\Mapping
 */
class WebhookEvent
{
    const EVENT_ARTICLE_ADDED = 'Article.added';
    const EVENT_ARTICLE_INITALSYNC = 'Article.initial_sync';
    const EVENT_ARTICLE_UPDATED = 'Article.updated';
    const EVENT_ARTICLE_REMOVED = 'Article.removed';
    const EVENT_ORDER_CANCELLED = 'Order.cancelled';
    const EVENT_ORDER_PROCESSED = 'Order.processed';
    const EVENT_SHIPMENT_SENT = 'Shipment.sent';

    public static function getAllEvents(): array
    {
        return [
            // article
            self::EVENT_ARTICLE_ADDED, self::EVENT_ARTICLE_UPDATED, self::EVENT_ARTICLE_REMOVED,

            // order
            self::EVENT_ORDER_CANCELLED, self::EVENT_ORDER_PROCESSED,

            // shipment
            self::EVENT_SHIPMENT_SENT
        ];
    }
}
