<?php

declare(strict_types=1);

namespace Spod\Sync\Subscriber\Webhook\Shipment;

use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\CrudManager\ShipmentManager;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

/**
 * Event handler which processes the
 * Shipment.sent webhook events.
 *
 * @package Spod\Sync\Subscriber\Webhook\Article
 */
class Sent extends BaseSubscriber
{
    /** @var ApiResultFactory  */
    private $apiResultFactory;

    /** @var ShipmentManager */
    private $shipmentManager;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        ShipmentManager $shipmentManager
    ) {
        $this->apiResultFactory = $apiResultFactory;
        $this->shipmentManager = $shipmentManager;
    }

    protected function processWebhookEvent(Webhook $webhookEvent): void
    {
        // decode and extract only shipment portion
        $payload = $webhookEvent->getDecodedPayload();
        $shipmentData = $payload->data->shipment;

        // put back json encoded shipment data
        $apiResult = $this->apiResultFactory->create();
        $apiResult->setPayload($shipmentData);

        $this->shipmentManager->addShipment($apiResult);
    }
}
