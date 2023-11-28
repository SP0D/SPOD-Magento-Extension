<?php

declare(strict_types=1);

namespace Spod\Sync\Cron;

use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\QueueProcessor\StockProcessor;

class Stock
{
    private SpodLoggerInterface $logger;

    private StockProcessor $stockProcessor;

    public function __construct(SpodLoggerInterface $logger, StockProcessor $stockProcessor)
    {
        $this->logger = $logger;
        $this->stockProcessor = $stockProcessor;
    }

    public function execute(): void
    {
        $this->logger->logDebug('Updates stock');
        $this->stockProcessor->updateStock();
    }
}
