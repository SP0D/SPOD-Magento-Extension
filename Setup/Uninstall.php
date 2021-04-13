<?php

namespace Spod\Sync\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
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
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->webhookHandler->unregisterWebhooks();
        $setup->getConnection()->dropTable($setup->getTable('spodsync_error_log'));
        $setup->getConnection()->dropTable($setup->getTable('spodsync_queue_orders'));
        $setup->getConnection()->dropTable($setup->getTable('spodsync_queue_webhook'));
        $setup->getConnection()->dropTable($setup->getTable('spodsync_status'));

        $this->attributeHelper->setSetup($setup);
        $this->attributeHelper->removeAttribute('spod_appearance');
        $this->attributeHelper->removeAttribute('spod_size');
        $this->attributeHelper->removeAttribute('spod_product_id');
        $this->attributeHelper->removeAttribute('spod_product_type_id');
        $this->attributeHelper->removeAttribute('spod_appearance_id');
        $this->attributeHelper->removeAttribute('spod_size_id');

        $setup->endSetup();
    }
}
