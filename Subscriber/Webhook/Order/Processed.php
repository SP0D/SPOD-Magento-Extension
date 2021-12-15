<?php
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
 * Order.processed webhook events.
 *
 * @package Spod\Sync\Subscriber\Webhook\Article
 */
class Processed extends BaseSubscriber
{
    protected $event = WebhookEvent::EVENT_ORDER_PROCESSED;

    /** @var OrderManager  */
    private $orderManager;

    public function __construct(
        SpodLoggerInterface $logger,
        OrderManager $orderManager,
        WebhookEventRepository $webhookEventRepository,
        StatusHelper $statusHelper
    ) {
        $this->logger = $logger;
        $this->orderManager = $orderManager;

        parent::__construct($webhookEventRepository, $statusHelper, $logger);
    }

    public function execute(Observer $observer)
    {
        $webhookEvent = $this->getWebhookEventFromObserver($observer);

        if ($this->isObserverResponsible($webhookEvent)) {
            $payload = $webhookEvent->getDecodedPayload();
            $spodOrderId = $payload->data->order->id;

            try {
                $this->orderManager->completeOrder((int) $spodOrderId);
                $this->setEventProcessed($webhookEvent);

            } catch (\Exception $e) {
                $this->logger->logError("order processed", $e->getMessage());
                $this->setEventFailed($webhookEvent);
            }
        }

        return $this;
    }
}
