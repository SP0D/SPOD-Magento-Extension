<?php

namespace Spod\Sync\Cron;

use Magento\Framework\App\State;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\QueueProcessor\OrderProcessor;

class Order
{
    /** @var SpodLoggerInterface */
    private $logger;

    /** @var OrderProcessor */
    private $orderProcessor;

    /** @var State */
    private $state;

    public function __construct(
        SpodLoggerInterface $logger,
        OrderProcessor $orderProcessor,
        State $state
    ) {
        $this->logger = $logger;
        $this->orderProcessor = $orderProcessor;
        $this->state = $state;
    }

    public function execute()
    {
        $this->logger->logDebug('Executing Order Cronjob');
        $this->orderProcessor->processPendingNewOrders();
    }
}
