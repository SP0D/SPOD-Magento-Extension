<?php

declare(strict_types=1);

namespace Spod\Sync\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\OrderRecord;
use Spod\Sync\Model\OrderRecordFactory;
use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\Repository\OrderRecordRepository;

/**
 * Magento 2 Plugin class which is reponsible
 * for placing new magento 2 orders in the
 * SPOD order queue.
 *
 * @package Spod\Sync\Plugin
 */
class OrderPlugin
{
    /** @var SpodLoggerInterface  */
    private $logger;

    /** @var OrderRecordFactory  */
    private $orderRecordFactory;

    /** @var OrderRecordRepository  */
    private $orderRecordRepository;

    public function __construct(
        OrderRecordFactory $orderRecordFactory,
        OrderRecordRepository $orderRecordRepository,
        SpodLoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->orderRecordFactory = $orderRecordFactory;
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

        $orderRecord = $this->orderRecordFactory->create();
        $orderRecord->setOrderId($magentoOrder->getId());
        $orderRecord->setStatus(QueueStatus::STATUS_PENDING);
        $orderRecord->setCreatedAt(new \DateTime());
        $orderRecord->setEventType(OrderRecord::RECORD_EVENT_TYPE_CREATE);
        $this->orderRecordRepository->save($orderRecord);
    }
}
