<?php

namespace Spod\Sync\Model\CrudManager;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;

use Magento\Quote\Model\Cart\ShippingMethod;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\ShipmentItemRepositoryInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\OrderRepository;
use Magento\Shipping\Model\ShipmentNotifier;
use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\ApiResult;

class ShipmentManager
{
    /** @var ConvertOrder */
    private $convertOrder;
    /** @var ResultDecoder */
    private $decoder;
    /** @var SpodLoggerInterface */
    private $logger;
    /** @var ItemRepository */
    private $orderItemRepository;
    /** @var OrderRepository */
    private $orderRepository;
    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;
    /** @var ShipmentRepositoryInterface */
    private $shipmentRepository;
    /** @var ShipmentItemRepositoryInterface */
    private $shipmentItemRepository;
    /** @var ShipmentNotifier */
    private $shipmentNotifier;
    /** @var ShipmentTrackInterfaceFactory */
    private $trackFactory;

    public function __construct(
        OrderRepository $orderRepository,
        ConvertOrder $convertOrder,
        ItemRepository $itemRepository,
        ResultDecoder $decoder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SpodLoggerInterface $logger,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentItemRepositoryInterface $shipmentItemRepository,
        ShipmentTrackInterfaceFactory $trackFactory,
        ShipmentNotifier $shipmentNotifier
    ) {
        $this->convertOrder = $convertOrder;
        $this->decoder = $decoder;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $itemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentItemRepository = $shipmentItemRepository;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->trackFactory = $trackFactory;
    }

    /**
     * @param ApiResult $apiResult
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addShipment(ApiResult $apiResult)
    {
        $apiShipment = $this->decoder->parsePayload($apiResult->getPayload());

        $order = $this->getOrderBySpodOrderId($apiShipment->orderId);
        if (!$this->canShip($order)) {
            throw new \Exception(
                sprintf('Cannot create a shipment for order id %s.', $order->getId())
            );
        }

        $magentoShipment = $this->createShipment($order, $apiShipment);
        $this->saveTracking($apiShipment, $magentoShipment);
        $this->shipmentNotifier->notify($magentoShipment);
    }

    /**
     * @param $spodOrderId
     * @return OrderInterface
     * @throws \Exception
     */
    protected function getOrderBySpodOrderId($spodOrderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('spod_order_id', $spodOrderId, 'eq')->create();
        $searchResults = $this->orderRepository->getList($searchCriteria);
        $orders = $searchResults->getItems();

        foreach ($orders as $order) {
            return $order;
        }

        throw new \Exception('SPOD Order Id not found');
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    private function canShip(OrderInterface $order)
    {
        return $order->canShip();
    }

    /**
     * @param OrderInterface $order
     * @param \stdClass $apiShipment
     * @return Shipment
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createShipment(OrderInterface $order, \stdClass $apiShipment)
    {
        $shipment = $this->convertOrder->toShipment($order);
        $itemsShipped = $this->getShippedItems($apiShipment);

        foreach ($itemsShipped as $orderItem) {
            $item = $this->getOrderItemOrParent($orderItem);
            $qtyShipped = $item->getQtyToShip();
            $shipmentItem = $this->convertOrder->itemToShipmentItem($item)->setQty($qtyShipped);
            $shipment->addItem($shipmentItem);
        }

        $shipment->register();
        $order->setIsInProcess(true);

        try {
            $this->shipmentRepository->save($shipment);
            $this->orderRepository->save($order);
            return $shipment;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param \stdClass $shippingData
     * @param Shipment $magentoShipment
     */
    private function saveTracking(\stdClass $shippingData, Shipment $magentoShipment)
    {
        foreach ($shippingData->tracking as $tracking) {
            $this->addTrackingToShipment($tracking, $magentoShipment);
        }
    }

    /**
     * @param \stdClass $apiShipment
     * @return array
     * @throws \Exception
     */
    private function getShippedItems(\stdClass $apiShipment)
    {
        $items = [];
        foreach ($apiShipment->orderItemIds as $spodOrderItemId) {
            $items[] = $this->getOrderItemBySpodOrderItemReference($spodOrderItemId);
        }

        return $items;
    }

    /**
     * @param $spodOrderItemId
     * @return \Magento\Framework\DataObject
     * @throws \Exception
     */
    private function getOrderItemBySpodOrderItemReference($spodOrderItemId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('spod_order_item_id', $spodOrderItemId, 'eq')->create();
        $searchResults = $this->orderItemRepository->getList($searchCriteria);
        $orderItems = $searchResults->getItems();

        foreach ($orderItems as $orderItem) {
            return $orderItem;
        }

        throw new \Exception('SPOD Order Item Id not found');
    }

    /**
     * @param OrderItemInterface $orderItem
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    private function getOrderItemOrParent(OrderItemInterface $orderItem): OrderItemInterface
    {
        $parentId = $orderItem->getParentItemId();
        if ($parentId) {
            return $this->orderItemRepository->get($parentId);
        } else {
            return $orderItem;
        }
    }

    /**
     * @param mixed $tracking
     * @param Order $order
     * @param Shipment $magentoShipment
     */
    private function addTrackingToShipment(\stdClass $tracking, Shipment $magentoShipment): void
    {
        $order = $magentoShipment->getOrder();

        try {
            $track = $this->trackFactory->create()->setNumber(
                $tracking->code
            )->setCarrierCode(
                $order->getShippingMethod()
            )->setTitle(
                $order->getShippingDescription()
            );
            $magentoShipment->addTrack($track);
            $this->shipmentRepository->save($magentoShipment);

        } catch (NoSuchEntityException $e) {
            $this->logger->logError("add tracking to shipment", "could not save tracking");
        }
    }
}
