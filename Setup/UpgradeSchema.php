<?php

namespace Spod\Sync\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            $this->createWebhookQueue($setup);
        }
        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $this->createOrderQueue($setup);
        }
        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $this->addSpodOrderIdToOrder($setup);
        }
        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $this->addSpodOrderReferenceToOrder($setup);
        }
        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $this->addSpodCancelledToOrder($setup);
        }
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createWebhookQueue(SchemaSetupInterface $setup): void
    {
        if (!$setup->tableExists('spodsync_queue_webhook')) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('spodsync_queue_webhook')
            )
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary' => true,
                        'unsigned' => true,
                    ],
                    'Queue ID'
                )
                ->addColumn(
                    'event_type',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable => false'],
                    'which event occured'
                )
                ->addColumn(
                    'status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    1,
                    ['nullable => false'],
                    'Status'
                )
                ->addColumn(
                    'payload',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '64k',
                    [],
                    'webhook request payload'
                )
                ->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Created At'
                )->addColumn(
                    'processed_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true],
                    'Processed At'
                )
                ->setComment('webhook queue');
            $setup->getConnection()->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createOrderQueue(SchemaSetupInterface $setup): void
    {
        if (!$setup->tableExists('spodsync_queue_orders')) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('spodsync_queue_orders')
            )
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary' => true,
                        'unsigned' => true,
                    ],
                    'Queue ID'
                )
                ->addColumn(
                    'order_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    ['nullable => false'],
                    'order id'
                )
                ->addColumn(
                    'status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    1,
                    ['nullable => false'],
                    'Status'
                )
                ->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Created At'
                )->addColumn(
                    'processed_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true],
                    'Processed At'
                )
                ->setComment('webhook queue');
            $setup->getConnection()->createTable($table);
        }
    }

    private function addSpodOrderIdToOrder(SchemaSetupInterface $setup): void
    {
        $setup->startSetup();

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'spod_order_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'size' => 255,
                'nullable' => true,
                'comment' => 'SPOD Order Id',
            ]
        );

        $setup->endSetup();
    }

    private function addSpodOrderReferenceToOrder(SchemaSetupInterface $setup): void
    {
        $setup->startSetup();

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'spod_order_reference',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'size' => 255,
                'nullable' => true,
                'comment' => 'SPOD Order Reference',
            ]
        );

        $setup->endSetup();
    }

    private function addSpodCancelledToOrder(SchemaSetupInterface $setup): void
    {
        $setup->startSetup();

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'spod_cancelled',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'size' => 1,
                'nullable' => true,
                'comment' => 'Order cancelled by SPOD, no API call required',
            ]
        );

        $setup->endSetup();
    }

}
