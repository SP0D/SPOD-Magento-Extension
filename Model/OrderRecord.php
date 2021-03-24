<?php
namespace Spod\Sync\Model;

use Spod\Sync\Api\ResultDecoder;

class OrderRecord extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'spod_sync_order';

    protected $_cacheTag = 'spod_sync_order';

    protected $_eventPrefix = 'spod_sync_order';

    /** @var ResultDecoder  */
    private $decoder;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
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
