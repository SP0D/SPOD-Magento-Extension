<?php

declare(strict_types=1);

namespace Spod\Sync\Cron;

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

    public function __construct(
        SpodLoggerInterface $logger,
        OrderProcessor $orderProcessor
    ) {
        $this->logger = $logger;
        $this->orderProcessor = $orderProcessor;
    }

    public function execute(): void
    {
        try {
            $this->logger->logDebug('[cron]: submitting pending orders');
            $this->orderProcessor->processPendingNewOrders();
        } catch (\Exception $e) {
            $this->logger->logError('process pending orders', $e->getMessage(), $e->getTraceAsString());
        }
    }
}
