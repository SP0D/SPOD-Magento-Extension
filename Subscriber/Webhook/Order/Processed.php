<?php

declare(strict_types=1);

namespace Spod\Sync\Subscriber\Webhook\Order;

use Spod\Sync\Model\CrudManager\OrderManager;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

/**
 * Event handler which processes the
 * Order.processed webhook events.
 *
 * @package Spod\Sync\Subscriber\Webhook\Article
 */
class Processed extends BaseSubscriber
{
    /** @var OrderManager  */
    private $orderManager;

    public function __construct(OrderManager $orderManager)
    {
        $this->orderManager = $orderManager;
    }

    protected function processWebhookEvent(Webhook $webhookEvent): void
    {
        $payload = $webhookEvent->getDecodedPayload();
        $spodOrderId = $payload->data->order->id;

        $this->orderManager->completeOrder((int) $spodOrderId);
    }
}
