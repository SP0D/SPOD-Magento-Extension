<?php

namespace Spod\Sync\Model\QueueProcessor;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Directory\Model\RegionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item;

use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Model\ApiReader\OrderHandler;
use Spod\Sync\Model\ApiResult;
use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\OrderExporter;
use Spod\Sync\Model\OrderRecord;
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

    public function __construct(
        CollectionFactory $collectionFactory,
        ConfigHelper $configHelper,
        OrderExporter $orderExporter,
        OrderHandler $orderHandler,
        OrderRepository $orderRepository,
        SpodLoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->orderExporter = $orderExporter;
        $this->orderHandler = $orderHandler;
        $this->orderRepository = $orderRepository;
    }

    public function processPendingOrders()
    {
        $collection = $this->getPendingOrderCollection();
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
    protected function getPendingOrderCollection(): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['eq' => QueueStatus::STATUS_PENDING]);

        return $collection;
    }

    /**
     * @param OrderRecord $order
     * @throws \Exception
     */
    private function submitOrder(OrderRecord $order)
    {
        $preparedOrder = $this->orderExporter->prepareOrder($order);
        $apiResult = $this->orderHandler->submitPreparedOrder($preparedOrder);
        if ($apiResult->getHttpCode() == self::HTTPSTATUS_ORDER_CREATED) {
            $this->saveSpodOrderId($apiResult, $order);
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
        $orderRecord->save();
    }

    /**
     * @param OrderRecord $orderRecord
     * @throws \Exception
     */
    public function setOrderRecordFailed(OrderRecord $orderRecord)
    {
        $orderRecord->setStatus(QueueStatus::STATUS_ERROR);
        $orderRecord->setProcessedAt(new \DateTime());
        $orderRecord->save();
    }

    /**
     * @param ApiResult $apiResult
     * @param OrderRecord $order
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function saveSpodOrderId(ApiResult $apiResult, OrderRecord $order): void
    {
        $apiResponse = $apiResult->getPayload();
        $magentoOrder = $this->orderRepository->get($order->getOrderId());
        $magentoOrder->setSpodOrderId($apiResponse->id);
        $magentoOrder->setSpodOrderReference($apiResponse->orderReference);
        $this->orderRepository->save($magentoOrder);
    }
}
