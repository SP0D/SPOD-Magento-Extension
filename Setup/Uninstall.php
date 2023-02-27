<?php

namespace Spod\Sync\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Spod\Sync\Model\ApiReader\WebhookHandler;
use Spod\Sync\Model\CrudManager\ProductManager;

/**
 * Magento Setup class which is used during the uninstall phase.
 *
 * @package Spod\Sync\Setup
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var WebhookHandler
     */
    private $webhookHandler;
    /**
     * @var ProductManager
     */
    private $productManager;

    public function __construct(
        WebhookHandler $webhookHandler,
        ProductManager $productManager
    ) {
        $this->productManager = $productManager;
        $this->webhookHandler = $webhookHandler;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->webhookHandler->unregisterWebhooks();
        $this->productManager->deleteAllSpodProducts();
        $setup->getConnection()->dropTable($setup->getTable('spodsync_log'));
        $setup->getConnection()->dropTable($setup->getTable('spodsync_queue_orders'));
        $setup->getConnection()->dropTable($setup->getTable('spodsync_queue_webhook'));
        $setup->getConnection()->dropTable($setup->getTable('spodsync_status'));

        $setup->endSetup();
    }
}
