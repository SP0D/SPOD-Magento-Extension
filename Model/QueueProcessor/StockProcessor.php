<?php

declare(strict_types=1);

namespace Spod\Sync\Model\QueueProcessor;

use Magento\Catalog\Model\ProductRepository;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\ApiReader\StockHandler;

class StockProcessor
{
    private SpodLoggerInterface $logger;

    private StockHandler $stockHandler;

    private ProductRepository $productRepository;

    public function __construct(
        SpodLoggerInterface $logger,
        StockHandler $stockHandler,
        ProductRepository $productRepository
    ) {
        $this->logger = $logger;
        $this->stockHandler = $stockHandler;
        $this->productRepository = $productRepository;
    }

    public function updateStock(): void
    {
        $rawStock = $this->stockHandler->fetchStock();
        foreach ($rawStock as $sku => $stockQty) {
            $this->logger->logDebug(sprintf('Updates %s stock %d', $sku, $stockQty));
            $this->updateProductStock($sku, (int) $stockQty);
        }
    }

    private function updateProductStock(string $sku, int $qty): void
    {
        $product = $this->productRepository->get($sku);
        if (!$product) {
            return;
        }
        $stockItem = null;
        if ($product->getExtensionAttributes() !== null) {
            $stockItem = $product->getExtensionAttributes()->getStockItem();
        }
        if ($stockItem === null) {
            return;
        }

        $stockItem->setQty($qty);
        $this->productRepository->save($product);
    }
}
