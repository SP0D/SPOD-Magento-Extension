<?php
namespace Spod\Sync\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\ApiReader\OrderHandler;
use Spod\Sync\Model\OrderRecord as QueueEntry;
use Spod\Sync\Model\OrderRecordFactory;
use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\Repository\OrderRecordRepository;

class OrderPlugin
{
    /** @var SpodLoggerInterface  */
    private $logger;
    /** @var OrderRecordFactory  */
    private $orderRecordFactory;
    /** @var OrderRecordRepository  */
    private $orderRecordRepository;
    /** @var OrderHandler */
    private OrderHandler $orderHandler;

    public function __construct(
        OrderHandler $orderHandler,
        OrderRecordFactory $orderRecordFactory,
        OrderRecordRepository $orderRecordRepository,
        SpodLoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->orderRecordFactory = $orderRecordFactory;
        $this->orderHandler = $orderHandler;
        $this->orderRecordRepository = $orderRecordRepository;
    }

    public function afterPlace(
        OrderManagementInterface $subject,
        OrderInterface $order
    ) {
        $orderItems = $order->getAllItems();

        foreach ($orderItems as $orderItem) {
            if ($orderItem->getProduct()->getSpodProduct()) {
                $this->createQueueEntry($order);
                break;
            }
        }

        return $order;
    }

    /**
     * @param OrderInterface $magentoOrder
     * @throws \Exception
     */
    private function createQueueEntry(OrderInterface $magentoOrder)
    {
        $this->logger->logDebug(sprintf("placing order #%s in queue", $magentoOrder->getId()));

        /** @var QueueEntry $queueEntry */
        $orderRecord = $this->orderRecordFactory->create();
        $orderRecord->setOrderId($magentoOrder->getId());
        $orderRecord->setStatus(QueueStatus::STATUS_PENDING);
        $orderRecord->setCreatedAt(new \DateTime());
        $this->orderRecordRepository->save($orderRecord);
    }
}
