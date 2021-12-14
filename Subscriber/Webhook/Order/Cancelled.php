<?php

declare(strict_types=1);

namespace Spod\Sync\Subscriber\Webhook\Order;

use Magento\Framework\Event\Observer;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\CrudManager\OrderManager;
use Spod\Sync\Model\Mapping\WebhookEvent;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

/**
 * Event handler which processes the
 * Order.cancelled webhook events.
 *
 * @package Spod\Sync\Subscriber\Webhook\Article
 */
class Cancelled extends BaseSubscriber
{
    protected $event = WebhookEvent::EVENT_ORDER_CANCELLED;

    /** @var OrderManager  */
    private $orderManager;

    public function __construct(
        SpodLoggerInterface $logger,
        OrderManager $orderManager,
        WebhookEventRepository $webhookEventRepository,
        StatusHelper $statusHelper
    ) {
        $this->orderManager = $orderManager;

        parent::__construct($webhookEventRepository, $statusHelper, $logger);
    }

    public function execute(Observer $observer)
    {
        $webhookEvent = $this->getWebhookEventFromObserver($observer);

        if ($this->isObserverResponsible($webhookEvent)) {
            $payload = $webhookEvent->getDecodedPayload();
            $spodOrderId = (int) $payload->data->order->id;

            try {
                $this->orderManager->cancelOrder($spodOrderId);
                $this->setEventProcessed($webhookEvent);

            } catch (\Exception $e) {
                $this->logger->logError("order cancelled", $e->getMessage());
                $this->setEventFailed($webhookEvent);
            }
        }

        return $this;
    }
}
