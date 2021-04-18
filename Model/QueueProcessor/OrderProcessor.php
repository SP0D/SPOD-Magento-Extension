<?php

namespace Spod\Sync\Model\QueueProcessor;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Sales\Model\OrderRepository;

use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Model\ApiReader\OrderHandler;
use Spod\Sync\Model\ApiResult;
use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\OrderExporter;
use Spod\Sync\Model\OrderRecord;
use Spod\Sync\Model\Repository\OrderRecordRepository;
use Spod\Sync\Model\ResourceModel\OrderRecord\Collection;
use Spod\Sync\Model\ResourceModel\OrderRecord\CollectionFactory;

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
    /** @var ConfigHelper */
    private $configHelper;
    /** @var ResultDecoder */
    private $decoder;

    public function __construct(
        CollectionFactory $collectionFactory,
        ConfigHelper $configHelper,
        OrderExporter $orderExporter,
        OrderHandler $orderHandler,
        ItemRepository $orderItemRepository,
        OrderRepository $orderRepository,
        OrderRecordRepository $orderRecordRepository,
        SpodLoggerInterface $logger,
        ResultDecoder $decoder
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->configHelper = $configHelper;
        $this->decoder = $decoder;
        $this->logger = $logger;
        $this->orderExporter = $orderExporter;
        $this->orderHandler = $orderHandler;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRecordRepository = $orderRecordRepository;
        $this->orderRepository = $orderRepository;
    }

    public function processPendingNewOrders()
    {
        $collection = $this->getPendingCreateOrderCollection();
        foreach ($collection as $order) {
            /** @var OrderRecord $order */
            $this->logger->logDebug(sprintf('Submitting order %s to API', $order->getOrderId()), "submitting order");

            try {
                $this->submitOrder($order);
                $this->setOrderRecordProcessed($order);
            } catch (\Exception $e) {
                $this->logger->logError(
                    "process pending orders",
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                $this->setOrderRecordFailed($order);
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

        return $collection;
    }

    /**
     * Export and submit an order using the
     * order handler.
     *
     * @param OrderRecord $order
     * @throws \Exception
     */
    private function submitOrder(OrderRecord $orderEvent)
    {
        $preparedOrder = $this->orderExporter->prepareOrder($orderEvent->getOrderId());
        $apiResult = $this->orderHandler->submitPreparedOrder($preparedOrder);
        $magentoOrder = $this->orderRepository->get($orderEvent->getOrderId());

        if ($apiResult->getHttpCode() == self::HTTPSTATUS_ORDER_CREATED) {
            $this->saveSpodOrderId($apiResult, $magentoOrder);
            $this->saveOrderItemIds($apiResult, $magentoOrder);
        }
    }

    /**
     * Mark queue entry for order as processed.
     *
     * @param OrderRecord $orderRecord
     * @throws \Exception
     */
    public function setOrderRecordProcessed(OrderRecord $orderRecord)
    {
        $orderRecord->setStatus(QueueStatus::STATUS_PROCESSED);
        $orderRecord->setProcessedAt(new \DateTime());
        $this->orderRecordRepository->save($orderRecord);
    }

    /**
     * Mark order queue record as failed.
     *
     * @param OrderRecord $orderRecord
     * @throws \Exception
     */
    public function setOrderRecordFailed(OrderRecord $orderRecord)
    {
        $orderRecord->setStatus(QueueStatus::STATUS_ERROR);
        $orderRecord->setProcessedAt(new \DateTime());
        $this->orderRecordRepository->save($orderRecord);
    }

    /**
     * Save SPOD order id, returned by API and available
     * in the ApiResult, alongside the Magento order for
     * later access.
     *
     * @param ApiResult $apiResult
     * @param OrderRecord $order
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function saveSpodOrderId(ApiResult $apiResult, OrderInterface $order): void
    {
        $apiResponse = $this->decoder->parsePayload($apiResult->getPayload());
        $order->setSpodOrderId($apiResponse->id);
        $order->setSpodOrderReference($apiResponse->orderReference);
        $this->orderRepository->save($order);
    }

    /**
     * Save order item ids in local sales_order_item table
     * for later reference.
     *
     * @param ApiResult $apiResult
     * @param OrderInterface $order
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function saveOrderItemIds(ApiResult $apiResult, OrderInterface $order): void
    {
        $apiResponse = $this->decoder->parsePayload($apiResult->getPayload());
        foreach ($apiResponse->orderItems as $apiResponseItem) {
            $salesOrderItem = $this->getItemFromOrderBySku($order, $apiResponseItem->sku);
            $salesOrderItem->setData('spod_order_item_id', $apiResponseItem->orderItemReference);
            $this->orderItemRepository->save($salesOrderItem);
        }
    }

    /**
     * Helper method which return an order item by SKU
     * from a certain order.
     *
     * @param OrderInterface $order
     * @param $sku
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     * @throws \Exception
     */
    private function getItemFromOrderBySku(OrderInterface $order, $sku)
    {
        $items = $order->getItems();
        foreach ($items as $item) {
            if ($item->getProduct()->getSku() == $sku) {
                return $item;
            }
        }

        throw new \Exception(sprintf("Item with sku %s not found in order", $sku));
    }

    /**
     * Updates a locally changed order using the API.
     *
     * @param $orderId
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateOrder($orderId)
    {
        $this->logger->logDebug(sprintf('trying to update order #%s', $orderId));
        $preparedOrder = $this->orderExporter->prepareOrder($orderId);
        $magentoOrder = $this->orderRepository->get($orderId);
        $this->logger->logDebug(sprintf('prepared order for update'));

        if ($this->orderHandler->updateOrder($magentoOrder->getSpodOrderId(), $preparedOrder)) {
            $this->logger->logDebug('order was updated');
        } else {
            $this->logger->logError('update order', 'order could not be updated');
            throw new \Exception(__("Order could not be updated"));
        }
    }
}
