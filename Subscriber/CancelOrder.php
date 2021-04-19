<?php

namespace Spod\Sync\Subscriber;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\ApiReader\OrderHandler;

/**
 * Magento 2 event subscriber which attempts
 * to cancel orders when they get cancelled in Magento.
 *
 * @package Spod\Sync\Subscriber
 */
class CancelOrder implements ObserverInterface
{
    /** @var OrderHandler */
    private $orderHandler;
    /** @var SpodLoggerInterface */
    private $logger;

    public function __construct(
        OrderHandler $orderHandler,
        SpodLoggerInterface $logger
    ) {
        $this->orderHandler = $orderHandler;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();
        if (!$order->getSpodOrderId() || $order->getSpodCancelled()) {
            return;
        }

        $this->logger->logDebug(sprintf('trying to cancel order #%s', $order->getId()));

        $spodOrderId = $order->getSpodOrderId();
        if ($this->orderHandler->cancelOrder($order->getSpodOrderId())) {
            $this->logger->logDebug(sprintf('%s order was cancelled', $spodOrderId));
        } else {
            $this->logger->logError('cancel order observer', 'order could not be cancelled');
            throw new \Exception(__("Order could not be cancelled"));
        }
    }
}
