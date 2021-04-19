<?php
namespace Spod\Sync\Model\ResourceModel\SpodLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * SpodLog Collection.
 * @package Spod\Sync\Model\ResourceModel\OrderRecord
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'spodsync_log_collection';
    protected $_eventObject = 'spodlog_collection';

    protected function _construct()
    {
        $this->_init('Spod\Sync\Model\SpodLog', 'Spod\Sync\Model\ResourceModel\SpodLog');
    }
}
