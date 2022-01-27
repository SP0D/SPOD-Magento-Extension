<?php

declare(strict_types=1);

namespace Spod\Sync\Model\QueueProcessor;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Sales\Model\OrderRepository;
use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\ApiReader\OrderHandler;
use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\OrderExporter;
use Spod\Sync\Model\OrderRecord;
use Spod\Sync\Model\Repository\OrderRecordRepository;
use Spod\Sync\Model\ResourceModel\OrderRecord\Collection;
use Spod\Sync\Model\ResourceModel\OrderRecord\CollectionFactory;

/**
 * Submits new and updated orders to the API
 * using the api handler for orders.
 *
 * @package Spod\Sync\Model\QueueProcessor
 */
class OrderProcessor
{
    const HTTPSTATUS_ORDER_CREATED = 201;

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var OrderExporter */
    private $orderExporter;

    /** @var OrderHandler  */
    private $orderHandler;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var SpodLoggerInterface  */
    private $logger;

    /** @var ItemRepository */
    private $orderItemRepository;

    /** @var OrderRecordRepository  */
    private $orderRecordRepository;

    /** @var ResultDecoder */
    private $decoder;

    public function __construct(
        CollectionFactory $collectionFactory,
        OrderExporter $orderExporter,
        OrderHandler $orderHandler,
        ItemRepository $orderItemRepository,
        OrderRepository $orderRepository,
        OrderRecordRepository $orderRecordRepository,
        SpodLoggerInterface $logger,
        ResultDecoder $decoder
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->decoder = $decoder;
        $this->logger = $logger;
        $this->orderExporter = $orderExporter;
        $this->orderHandler = $orderHandler;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRecordRepository = $orderRecordRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Entry point which triggers processing of all currently pending orders.
     */
    public function processPendingNewOrders(): void
    {
        /** @var OrderRecord[] $collection */
        $collection = $this->getPendingCreateOrderCollection();
        foreach ($collection as $orderRecord) {
            $this->logger->logDebug(
                sprintf('Submitting order %s to API', $orderRecord->getOrderId()),
                'submitting order'
            );
            /** @var OrderInterface|Order $magentoOrder */
            $magentoOrder = $this->orderRepository->get($orderRecord->getOrderId());
            try {
                $areSpodProductsPaid = function (OrderInterface $order) {
                    $spodOrderItems = array_filter(
                        $order->getItems(),
                        function (OrderItemInterface $orderItem) {
                            return $orderItem->getProduct() && $orderItem->getProduct()->getData('spod_product_id') > 0;
                        }
                    );
                    if (0 === count($spodOrderItems)) {
                        throw new \Exception(sprintf('Order #%s does not contain SPOD products.', $order->getIncrementId()));
                    }
                    $spodOrderItemsToBePaid = array_filter(
                        $spodOrderItems,
                        function (OrderItemInterface $orderItem) {
                            return $orderItem->getQtyToInvoice() > 0;
                        }
                    );
                    return 0 === count($spodOrderItemsToBePaid);
                };

                if (!$areSpodProductsPaid($magentoOrder) || Order::STATE_PROCESSING !== $magentoOrder->getState()) {
                    if (Order::STATE_CLOSED === $magentoOrder->getState()
                        || Order::STATE_CANCELED === $magentoOrder->getState()) {
                        throw new \Exception(
                            sprintf(
                                'Order #%s is in state %s. Declining sync for this order...',
                                $magentoOrder->getIncrementId(),
                                $magentoOrder->getState()
                            )
                        );
                    }
                    continue;
                }

                $spodOrder = $this->orderExporter->prepareOrder($magentoOrder);
                $apiResult = $this->orderHandler->submitPreparedOrder($spodOrder);

                if ($apiResult->getHttpCode() !== self::HTTPSTATUS_ORDER_CREATED) {
                    throw new \Exception(sprintf('Failed to submit order #%s', $magentoOrder->getIncrementId()));
                }

                $apiResponse = $this->decoder->parsePayload($apiResult->getPayload());
                $magentoOrder->setData('spod_order_id', $apiResponse->id);
                $magentoOrder->setData('spod_order_reference', $apiResponse->orderReference);
                foreach ($apiResponse->orderItems as $spodOrderItem) {
                    $salesOrderItem = $this->getItemFromOrderBySku($magentoOrder, $spodOrderItem->sku);
                    $salesOrderItem->setData('spod_order_item_id', $spodOrderItem->orderItemReference);
                    if ($salesOrderItem->getParentItem()) {
                        $salesOrderItem->getParentItem()->setLockedDoShip(true);
                        $salesOrderItem->setLockedDoShip(true);
                    }
                    $this->orderItemRepository->save($salesOrderItem);
                }
                $magentoOrder->addCommentToStatusHistory(
                    sprintf('Order was synced to SPOD. SPOD order reference is %s', $apiResponse->orderReference),
                    false,
                    false
                );
                $orderRecord->setStatus(QueueStatus::STATUS_PROCESSED);
                $orderRecord->setProcessedAt(new \DateTimeImmutable());
            } catch (\Exception $e) {
                $this->logger->logError('process pending orders', $e->getMessage(), $e->getTraceAsString());
                $magentoOrder->addCommentToStatusHistory(
                    'Could not sync this order to SPOD',
                    false,
                    false
                );
                $orderRecord->setStatus(QueueStatus::STATUS_ERROR);
                $orderRecord->setProcessedAt(new \DateTimeImmutable());
            } finally {
                $this->orderRepository->save($magentoOrder);
                $this->orderRecordRepository->save($orderRecord);
            }
        }
    }

    /**
     * Filter order queue and return a collection
     * of pending / unprocessed entries.
     *
     * @return Collection
     */
    protected function getPendingCreateOrderCollection(): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['eq' => QueueStatus::STATUS_PENDING]);
        $collection->addFieldToFilter('event_type', ['eq' => OrderRecord::RECORD_EVENT_TYPE_CREATE]);
        $collection->addOrder('created_at', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        return $collection;
    }

    /**
     * Helper method which return an order item by SKU
     * from a certain order.
     *
     * @param OrderInterface $order
     * @param string $sku
     * @return OrderItemInterface
     * @throws \Exception
     */
    private function getItemFromOrderBySku(OrderInterface $order, string $sku): OrderItemInterface
    {
        $items = $order->getItems();
        foreach ($items as $item) {
            if ($item->getProduct()->getSku() == $sku) {
                return $item;
            }
        }

        throw new \Exception(sprintf('Order has not Item with sku %s', $sku));
    }

    /**
     * Updates a locally changed order using the API.
     *
     * @param integer $orderId
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateOrder(int $orderId): void
    {
        $this->logger->logDebug(sprintf('trying to update order #%d', $orderId));
        $magentoOrder = $this->orderRepository->get($orderId);
        $preparedOrder = $this->orderExporter->prepareOrder($magentoOrder);
        $this->logger->logDebug(sprintf('prepared order #%d for update', $orderId));

        $spodOrderId = (int) $magentoOrder->getData('spod_order_id');
        if (!$spodOrderId) {
            throw new \Exception(sprintf('SPOD order id is missing for Order #%s', $magentoOrder->getIncrementId()));
        }

        if ($this->orderHandler->updateOrder($spodOrderId, $preparedOrder)) {
            $this->logger->logDebug('order was updated');
        } else {
            $this->logger->logError('update order', 'order could not be updated');
            throw new \Exception(__("Order could not be updated"));
        }
    }
}
