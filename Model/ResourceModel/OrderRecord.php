<?php
namespace Spod\Sync\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class OrderRecord extends AbstractDb
{
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('spodsync_queue_orders', 'id');
    }
}
