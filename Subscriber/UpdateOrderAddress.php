<?php

declare(strict_types=1);

namespace Spod\Sync\Subscriber;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\QueueProcessor\OrderProcessor;

/**
 * Magento 2 event subscriber which updates orders
 * when non-cancelling changes occur (shipping address for example).
 *
 * @package Spod\Sync\Subscriber
 */
class UpdateOrderAddress implements ObserverInterface
{
    /** @var SpodLoggerInterface */
    private $logger;

    /** @var OrderProcessor */
    private $orderProcessor;

    /**
     * UpdateOrderAddress constructor.
     *
     * @param OrderProcessor $orderProcessor
     * @param SpodLoggerInterface $logger
     */
    public function __construct(
        OrderProcessor $orderProcessor,
        SpodLoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->orderProcessor = $orderProcessor;
    }

    /**
     *
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer): void
    {
        $address = $observer->getAddress();
        // new objects are not getting updated
        if ($address->isObjectNew() || !$address->getOrigData('entity_id')) {
            return;
        }

        $this->logger->logDebug(sprintf('Updating Order ID: %s', $address->getParentId()));
        $orderId = $address->getParentId();
        $this->orderProcessor->updateOrder((int) $orderId);
    }
}
