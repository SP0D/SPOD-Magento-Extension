<?php

namespace Spod\Sync\Subscriber;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\ApiReader\OrderHandler;

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

        if (!$order->getSpodOrderId()) {
            return;
        }

        if ($this->orderHandler->cancelOrder($order->getSpodOrderId())) {
            $this->logger->logDebug('order was cancelled');
        } else {
            $this->logger->logError('order could not be cancelled');
            throw new \Exception(__("Order could not be cancelled"));
        }
    }
}
