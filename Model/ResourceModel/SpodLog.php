<?php

namespace Spod\Sync\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Magento 2 resource model for spod log entries.
 *
 * @package Spod\Sync\Model\ResourceModel
 */
class SpodLog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('spodsync_log', 'id');
    }

}
