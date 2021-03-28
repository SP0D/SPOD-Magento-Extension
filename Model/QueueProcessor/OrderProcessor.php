<?php

namespace Spod\Sync\Model\QueueProcessor;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Sales\Model\OrderRepository;

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

    public function __construct(
        CollectionFactory $collectionFactory,
        ConfigHelper $configHelper,
        OrderExporter $orderExporter,
        OrderHandler $orderHandler,
        ItemRepository $orderItemRepository,
        OrderRepository $orderRepository,
        OrderRecordRepository $orderRecordRepository,
        SpodLoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->orderExporter = $orderExporter;
        $this->orderHandler = $orderHandler;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRecordRepository = $orderRecordRepository;
    }

    public function processPendingNewOrders()
    {
        $collection = $this->getPendingCreateOrderCollection();
        foreach ($collection as $order) {
            try {
                $this->submitOrder($order);
                $this->setOrderRecordProcessed($order);
            } catch (\Exception $e) {
                $this->logger->logError($e->getMessage());
                $this->setOrderRecordFailed($order);
            }
        }
    }

    /**
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
     * @param ApiResult $apiResult
     * @param OrderRecord $order
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function saveSpodOrderId(ApiResult $apiResult, OrderInterface $order): void
    {
        $apiResponse = $apiResult->getPayload();
        $order->setSpodOrderId($apiResponse->id);
        $order->setSpodOrderReference($apiResponse->orderReference);
        $this->orderRepository->save($order);
    }

    /**
     * @param ApiResult $apiResult
     * @param OrderInterface $order
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function saveOrderItemIds(ApiResult $apiResult, OrderInterface $order): void
    {
        $apiResponse = $apiResult->getPayload();
        foreach ($apiResponse->orderItems as $apiResponseItem) {
            $salesOrderItem = $this->getItemFromOrderBySku($order, $apiResponseItem->sku);
            $salesOrderItem->setData('spod_order_item_id', $apiResponseItem->orderItemReference);
            $this->orderItemRepository->save($salesOrderItem);
        }
    }

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

    public function updateOrder($orderId)
    {
        $this->logger->logDebug(sprintf('trying to update order #%s', $orderId));
        $preparedOrder = $this->orderExporter->prepareOrder($orderId);
        $magentoOrder = $this->orderRepository->get($orderId);
        $this->logger->logDebug(sprintf('prepared order for update'));

        if ($this->orderHandler->updateOrder($magentoOrder->getSpodOrderId(), $preparedOrder)) {
            $this->logger->logDebug('order was updated');

        } else {
            $this->logger->logError('order could not be updated');
            throw new \Exception(__("Order could not be updated"));
        }
    }
}
