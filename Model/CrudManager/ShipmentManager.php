<?php

declare(strict_types=1);

namespace Spod\Sync\Model\CrudManager;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterface;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterfaceFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\ShipmentDocumentFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Shipping\Model\ShipmentNotifier;
use Spod\Sync\Model\ApiResult;

/**
 * Applies tracking and shipping informations
 * to Magento orders.
 *
 * @package Spod\Sync\Model\CrudManager
 */
class ShipmentManager
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var ShipmentRepositoryInterface */
    private $shipmentRepository;

    /** @var ShipmentNotifier */
    private $shipmentNotifier;

    /** @var ShipmentDocumentFactory */
    private $shipmentFactory;

    /** @var ShipmentItemCreationInterfaceFactory */
    private $itemFactory;

    /**
     * @var ShipmentTrackCreationInterfaceFactory
     */
    private $trackFactory;

    public function __construct(
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentNotifier $shipmentNotifier,
        ShipmentDocumentFactory $shipmentFactory,
        ShipmentItemCreationInterfaceFactory $itemFactory,
        ShipmentTrackCreationInterfaceFactory $trackFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->shipmentFactory = $shipmentFactory;
        $this->itemFactory = $itemFactory;
        $this->trackFactory = $trackFactory;
    }

    public function addShipment(ApiResult $apiResult): void
    {
        $apiShipment = $apiResult->getPayload();
        /** @var OrderInterface|Order $order */
        $order = $this->getOrderBySpodOrderId($apiShipment->orderId);

        // Order items contain SPOD products
        $orderItems = array_filter(
            $order->getItems(),
            function (OrderItemInterface $orderItem) {
                return intval($orderItem->getData('spod_order_item_id'));
            }
        );

        $spodOrderItemIds = array_values($apiShipment->orderItemIds);
        array_walk(
            $orderItems,
            function (OrderItemInterface $orderItem) use($spodOrderItemIds) {
                $spodOrderItemId = $orderItem->getData('spod_order_item_id');
                if (in_array($spodOrderItemId, $spodOrderItemIds)) {
                    if ($orderItem->getParentItem()) {
                        $orderItem->getParentItem()->setLockedDoShip(false);
                        $orderItem->setLockedDoShip(false);
                    }
                }
            }
        );

        if (!$order->canShip()) {
            throw new \Exception(
                sprintf('Cannot create a shipment for order id #%s.', $order->getIncrementId())
            );
        }

        $searchOrderItem = function (int $spodOrderItemId) use ($orderItems) {
            $filtered = array_filter(
                $orderItems,
                function (OrderItemInterface $orderItem) use ($spodOrderItemId) {
                    return intval($orderItem->getData('spod_order_item_id')) === $spodOrderItemId;
                }
            );
            return array_shift($filtered);
        };

        $shipmentItems = [];
        foreach ($spodOrderItemIds as $spodOrderItemId) {
            $orderItem = $searchOrderItem((int) $spodOrderItemId);
            if (!$orderItem) {
                continue;
            }
            if ($orderItem->getParentItem()) {
                $orderItem = $orderItem->getParentItem();
            }
            /** @var ShipmentItemCreationInterface $item */
            $item = $this->itemFactory->create();
            $item->setOrderItemId($orderItem->getId());
            $item->setQty($orderItem->getQtyToShip());
            $shipmentItems[] = $item;
        }

        $tracking = [];
        foreach ($apiShipment->tracking as $trackingItem) {
            /** @var ShipmentTrackCreationInterface $each */
            $each = $this->trackFactory->create();
            $each->setTrackNumber($trackingItem->code);
            $each->setTitle($order->getShippingDescription());
            $each->setCarrierCode($order->getShippingMethod());
            $tracking[] = $each;
        }

        $shipment = $this->shipmentFactory
            ->create(
                $order,
                $shipmentItems,
                $tracking
            );

        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        $order->addCommentToStatusHistory(
            'Shipment was created due to notification from SPOD.'
        );

        $this->shipmentRepository->save($shipment);
        $this->orderRepository->save($order);

        $this->shipmentNotifier->notify($shipment);
    }

    protected function getOrderBySpodOrderId(int $spodOrderId): OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('spod_order_id', $spodOrderId, 'eq')
            ->create();
        $searchResults = $this->orderRepository->getList($searchCriteria);
        $orders = $searchResults->getItems();

        foreach ($orders as $order) {
            return $order;
        }

        throw new \Exception('SPOD Order Id not found');
    }
}
