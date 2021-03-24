<?php
namespace Spod\Sync\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\OrderRecord as QueueEntry;
use Spod\Sync\Model\OrderRecordFactory;
use Spod\Sync\Model\Mapping\QueueStatus;

class OrderPlugin
{
    private $logger;
    private $orderFactory;

    public function __construct(
        OrderRecordFactory $orderFactory,
        SpodLoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
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
     * @param Order $magentoOrder
     * @throws \Exception
     */
    private function createQueueEntry(Order $magentoOrder)
    {
        $this->logger->logDebug(sprintf("placing order #%s in queue", $magentoOrder->getId()));

        /** @var QueueEntry $queueEntry */
        $queueEntry = $this->orderFactory->create();
        $queueEntry->setOrderId($magentoOrder->getId());
        $queueEntry->setStatus(QueueStatus::STATUS_PENDING);
        $queueEntry->setCreatedAt(new \DateTime());
        $queueEntry->save();
    }
}
