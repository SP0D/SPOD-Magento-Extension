<?php
namespace Spod\Sync\Model\ResourceModel\OrderRecord;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'spod_sync_order_collection';
    protected $_eventObject = 'order_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Spod\Sync\Model\OrderRecord', 'Spod\Sync\Model\ResourceModel\OrderRecord');
    }

}
