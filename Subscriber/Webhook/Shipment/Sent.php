<?php
namespace Spod\Sync\Subscriber\Webhook\Shipment;

use Magento\Framework\Event\Observer;
use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\CrudManager\ShipmentManager;
use Spod\Sync\Model\Mapping\WebhookEvent;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

class Sent extends BaseSubscriber
{
    protected $event = WebhookEvent::EVENT_SHIPMENT_SENT;

    /** @var ApiResultFactory  */
    private $apiResultFactory;
    /** @var PayloadEncoder  */
    private $encoder;
    /** @var SpodLoggerInterface  */
    private $logger;
    /** @var ShipmentManager */
    private $shipmentManager;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        PayloadEncoder $encoder,
        SpodLoggerInterface $logger,
        ShipmentManager $shipmentManager,
        WebhookEventRepository $webhookEventRepository
    ) {
        $this->apiResultFactory = $apiResultFactory;
        $this->encoder = $encoder;
        $this->logger = $logger;
        $this->shipmentManager = $shipmentManager;

        parent::__construct($webhookEventRepository);
    }

    public function execute(Observer $observer)
    {
        $webhookEvent = $this->getWebhookEventFromObserver($observer);

        if ($this->isObserverResponsible($webhookEvent)) {
            $payload = $webhookEvent->getDecodedPayload();
            $shipmentData = $payload->data->shipment;

            try {
                $apiResult = $this->apiResultFactory->create();
                $apiResult->setPayload($this->encoder->encodePayload($shipmentData));

                $this->shipmentManager->addShipment($apiResult);
                $this->setEventProcessed($webhookEvent);

            } catch (\Exception $e) {
                $this->logger->logError($e->getMessage());
                $this->setEventFailed($webhookEvent);
            }
        }

        return $this;
    }


}
