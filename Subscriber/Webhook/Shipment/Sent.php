<?php
namespace Spod\Sync\Subscriber\Webhook\Shipment;

use Magento\Framework\Event\Observer;
use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\CrudManager\ShipmentManager;
use Spod\Sync\Model\Mapping\WebhookEvent;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

/**
 * Event handler which processes the
 * Shipment.sent webhook events.
 *
 * @package Spod\Sync\Subscriber\Webhook\Article
 */
class Sent extends BaseSubscriber
{
    protected $event = WebhookEvent::EVENT_SHIPMENT_SENT;

    /** @var ApiResultFactory  */
    private $apiResultFactory;
    /** @var PayloadEncoder  */
    private $encoder;
    /** @var ShipmentManager */
    private $shipmentManager;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        PayloadEncoder $encoder,
        SpodLoggerInterface $logger,
        ShipmentManager $shipmentManager,
        WebhookEventRepository $webhookEventRepository,
        StatusHelper $statusHelper
    ) {
        $this->apiResultFactory = $apiResultFactory;
        $this->encoder = $encoder;
        $this->shipmentManager = $shipmentManager;

        parent::__construct($webhookEventRepository, $statusHelper, $logger);
    }

    public function execute(Observer $observer)
    {
        $webhookEvent = $this->getWebhookEventFromObserver($observer);

        if ($this->isObserverResponsible($webhookEvent)) {
            // decode and extract only shipment portion
            $payload = $webhookEvent->getDecodedPayload();
            $shipmentData = $payload->data->shipment;

            try {
                // put back json encoded shipment data
                $apiResult = $this->apiResultFactory->create();
                $jsonShipmentData = $this->encoder->encodePayload($shipmentData);
                $apiResult->setPayload($jsonShipmentData);

                $this->shipmentManager->addShipment($apiResult);
                $this->setEventProcessed($webhookEvent);

            } catch (\Exception $e) {
                $this->logger->logError("shipment sent", $e->getMessage());
                $this->setEventFailed($webhookEvent);
            }
        }

        return $this;
    }
}
