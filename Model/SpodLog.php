<?php

namespace Spod\Sync\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Model that represents log entries in the SPOD
 * Log database.
 *
 * @package Spod\Sync\Model
 */
class SpodLog extends AbstractModel implements IdentityInterface
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
