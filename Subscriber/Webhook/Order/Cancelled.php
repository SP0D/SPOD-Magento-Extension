<?php
namespace Spod\Sync\Subscriber\Webhook\Order;

use Magento\Framework\Event\Observer;
use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\CrudManager\OrderManager;
use Spod\Sync\Model\Mapping\WebhookEvent;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

class Cancelled extends BaseSubscriber
{
    protected $event = WebhookEvent::EVENT_ORDER_CANCELLED;

    /** @var ApiResultFactory  */
    private $apiResultFactory;
    /** @var PayloadEncoder  */
    private $encoder;
    /** @var OrderManager  */
    private $orderManager;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        PayloadEncoder $encoder,
        SpodLoggerInterface $logger,
        OrderManager $orderManager,
        WebhookEventRepository $webhookEventRepository,
        StatusHelper $statusHelper
    ) {
        $this->apiResultFactory = $apiResultFactory;
        $this->encoder = $encoder;
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
