<?php

namespace Spod\Sync\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Magento Setup class which adds requires database tables.
 *
 * @package Spod\Sync\Setup
 */
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
        if (version_compare($context->getVersion(), '1.0.8') < 0) {
            $this->addSpodOrderItemIdToSalesOrderItem($setup);
        }
        if (version_compare($context->getVersion(), '1.0.9') < 0) {
            $this->addEventTypeToOrderQueue($setup);
        }
        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $this->createLogTable($setup);
        }
        if (version_compare($context->getVersion(), '1.1.1') < 0) {
            $this->createStatusTable($setup);
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
                    Table::TYPE_INTEGER,
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
                    Table::TYPE_TEXT,
                    255,
                    ['nullable => false'],
                    'which event occured'
                )
                ->addColumn(
                    'status',
                    Table::TYPE_INTEGER,
                    1,
                    ['nullable => false'],
                    'Status'
                )
                ->addColumn(
                    'payload',
                    Table::TYPE_TEXT,
                    '64k',
                    [],
                    'webhook request payload'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )->addColumn(
                    'processed_at',
                    Table::TYPE_TIMESTAMP,
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
                    Table::TYPE_INTEGER,
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
                    Table::TYPE_INTEGER,
                    11,
                    ['nullable => false'],
                    'order id'
                )
                ->addColumn(
                    'status',
                    Table::TYPE_INTEGER,
                    1,
                    ['nullable => false'],
                    'Status'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )->addColumn(
                    'processed_at',
                    Table::TYPE_TIMESTAMP,
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
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'nullable' => true,
                'comment' => 'SPOD Order Id',
            ]
        );

        $setup->endSetup();
    }

    private function addSpodOrderItemIdToSalesOrderItem(SchemaSetupInterface $setup): void
    {
        $setup->startSetup();

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_item'),
            'spod_order_item_id',
            [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'nullable' => true,
                'comment' => 'SPOD Order Item Id',
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
                'type' => Table::TYPE_TEXT,
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
                'type' => Table::TYPE_INTEGER,
                'size' => 1,
                'nullable' => true,
                'comment' => 'Order cancelled by SPOD, no API call required',
            ]
        );

        $setup->endSetup();
    }

    private function addEventTypeToOrderQueue(SchemaSetupInterface $setup): void
    {
        $setup->startSetup();

        $setup->getConnection()->addColumn(
            $setup->getTable('spodsync_queue_orders'),
            'event_type',
            [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'nullable' => false,
                'comment' => 'Create new or update existing order',
            ]
        );

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createLogTable(SchemaSetupInterface $setup): void
    {
        if (!$setup->tableExists('spodsync_log')) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('spodsync_log')
            )
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
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
                    'event',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable => false'],
                    'what type of event was processed'
                )
                ->addColumn(
                    'message',
                    Table::TYPE_TEXT,
                    '64k',
                    [],
                    'the error message'
                )
                ->addColumn(
                    'payload',
                    Table::TYPE_TEXT,
                    '64k',
                    ['nullable' => true],
                    'the payload that was processed, if any'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->setComment('error log');
            $setup->getConnection()->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createStatusTable(SchemaSetupInterface $setup): void
    {
        if (!$setup->tableExists('spodsync_status')) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('spodsync_status')
            )
                ->addColumn(
                    'installed_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true],
                    'Installer Executed At'
                )
                ->addColumn(
                    'initsync_start_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true],
                    'Initial Sync Started At'
                )
                ->addColumn(
                    'initsync_end_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true],
                    'Initial Sync Ended At'
                )
                ->addColumn(
                    'lastsync_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true],
                    'Last Sync'
                )
                ->setComment('Status Table');
            $setup->getConnection()->createTable($table);

            // status has only one row, create it right away
            $setup->getConnection()->query(sprintf('INSERT INTO %s VALUES (NULL, NULL, NULL, NULL)', $setup->getTable('spodsync_status')));
        }
    }
}
