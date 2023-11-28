<?php

declare(strict_types=1);

namespace Spod\Sync\Model\ApiReader;

class StockHandler extends AbstractHandler
{
    const ACTION_BASE_URL = '/stock?limit=%d&offset=%d';

    public function fetchStock(): array
    {
        $limit = 100;
        $offset = 0;
        $items = [];
        $continue = true;
        while ($continue) {
            $result = $this->fetchResult(sprintf(self::ACTION_BASE_URL, $limit, $offset));
            $payload = $result->getPayload();
            $count = $payload->count;
            $limit = $payload->limit;
            $offset = $payload->offset;
            $items = array_merge($items, (array) $payload->items);
            $continue = ($offset + $limit) < $count;
            $offset = $offset + $limit;
        }
        return $items;
    }
}
