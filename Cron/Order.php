<?php

namespace Spod\Sync\Cron;

use Magento\Framework\App\State;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\QueueProcessor\OrderProcessor;

/**
 * Cronjob for orders. Magento orders are
 * not processed directly, but added to a local queue.
 * This classed is reponsible for submitting them
 * to the SPOD API.
 *
 * @package Spod\Sync\Cron
 */
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
        $this->logger->logDebug('[cron]: submitting pending orders');
        $this->orderProcessor->processPendingNewOrders();
    }
}
