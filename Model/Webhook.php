<?php

namespace Spod\Sync\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Magento 2 Model that represents webhook requests internally.
 *
 * @package Spod\Sync\Model
 */
class Webhook extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'spod_sync_webhook';

    protected $_cacheTag = 'spod_sync_webhook';

    protected $_eventPrefix = 'spod_sync_webhook';

    protected function _construct()
    {
        $this->_init('Spod\Sync\Model\ResourceModel\Webhook');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDecodedPayload()
    {
        return json_decode($this->getPayload());
    }
}
