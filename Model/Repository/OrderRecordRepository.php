<?php

namespace Spod\Sync\Model\Repository;

use Spod\Sync\Model\OrderRecord;
use Spod\Sync\Model\ResourceModel\OrderRecord as OrderRecordResource;

/**
 * Custom repository which handles saving of
 * OrderRecord models by using the resource model.
 *
 * @package Spod\Sync\Model\Repository
 */
class OrderRecordRepository
{
    /** @var OrderRecordResource  */
    private $orderRecordResource;

    public function __construct(
        OrderRecordResource $orderRecordResource
    ) {
        $this->orderRecordResource = $orderRecordResource;
    }

    public function save(OrderRecord $orderRecord)
    {
        $this->orderRecordResource->save($orderRecord);
    }
}
