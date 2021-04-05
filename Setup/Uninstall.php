<?php

namespace Spod\Sync\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Spod\Sync\Helper\AttributeHelper;
use Spod\Sync\Model\ApiReader\WebhookHandler;

class Uninstall implements UninstallInterface
{
    /**
     * @var AttributeHelper
     */
    private $attributeHelper;
    /**
     * @var WebhookHandler
     */
    private $webhookHandler;

    public function __construct(
        AttributeHelper $attributeHelper,
        WebhookHandler $webhookHandler
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->webhookHandler = $webhookHandler;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function uninstall(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->webhookHandler->unregisterWebhooks();
        $setup->getConnection()->dropTable($setup->getTable('spodsync_error_log'));
        $setup->getConnection()->dropTable($setup->getTable('spodsync_queue_orders'));
        $setup->getConnection()->dropTable($setup->getTable('spodsync_queue_webhook'));
        $setup->getConnection()->dropTable($setup->getTable('spodsync_status'));
        $setup->endSetup();
    }
}
