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
 * Magento 2 Model that represents webhook requests internally.
 *
 * @package Spod\Sync\Model
 */
class Webhook extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'spod_sync_webhook';

    protected $_cacheTag = 'spod_sync_webhook';

    protected $_eventPrefix = 'spod_sync_webhook';

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
        $this->_init('Spod\Sync\Model\ResourceModel\Webhook');
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

    public function getDecodedPayload()
    {
        return $this->decoder->parsePayload($this->getPayload());
    }
}
