<?php
namespace Spod\Sync\Model\ResourceModel;

class Webhook extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    )
    {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('spodsync_queue_order', 'id');
    }

}
