<?php

namespace Spod\Sync\Model\Repository;

use Spod\Sync\Model\OrderRecord;
use Spod\Sync\Model\ResourceModel\OrderRecord as OrderRecordResource;

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
