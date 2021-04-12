<?php

namespace Spod\Sync\Model;

class SpodLog extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'spodsync_log';

    protected $_cacheTag = 'spodsync_log';

    protected $_eventPrefix = 'spodsync_log';

    protected function _construct()
    {
        $this->_init('Spod\Sync\Model\ResourceModel\SpodLog');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}
