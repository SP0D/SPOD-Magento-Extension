<?php
namespace Spod\Sync\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Spod\Sync\Api\ResultDecoder;

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

    /** @var ResultDecoder  */
    private $decoder;

    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        ResultDecoder $decoder,
        array $data = [])
    {
        $this->decoder = $decoder;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Spod\Sync\Model\ResourceModel\OrderRecord');
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
