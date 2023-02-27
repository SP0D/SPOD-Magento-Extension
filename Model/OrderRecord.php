<?php

namespace Spod\Sync\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Model which represents entries in the order
 * queue which gets submitted to the SPOD API.
 *
 * @package Spod\Sync\Model
 */
class OrderRecord extends AbstractModel implements IdentityInterface
{
    const RECORD_EVENT_TYPE_CREATE = 'create';
    const RECORD_EVENT_TYPE_UPDATE = 'update';

    const CACHE_TAG = 'spod_sync_order';

    protected $_cacheTag = 'spod_sync_order';

    protected $_eventPrefix = 'spod_sync_order';

    protected function _construct()
    {
        $this->_init('Spod\Sync\Model\ResourceModel\OrderRecord');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
