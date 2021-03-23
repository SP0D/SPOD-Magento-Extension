<?php
namespace Spod\Sync\Model\ResourceModel\Webhook;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'spod_sync_webhook_collection';
    protected $_eventObject = 'webhook_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Spod\Sync\Model\Webhook', 'Spod\Sync\Model\ResourceModel\Webhook');
    }

}
