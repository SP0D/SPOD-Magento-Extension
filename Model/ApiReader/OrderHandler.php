<?php

namespace Spod\Sync\Model\ApiReader;

use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Model\ApiResult;
use Spod\Sync\Model\ApiResultFactory;

class OrderHandler extends AbstractHandler
{
    const ACTION_BASE_URL = '/orders';

    public function __construct(
        ApiResultFactory $apiResultFactory,
        ConfigHelper $configHelper,
        PayloadEncoder $encoder,
        ResultDecoder $decoder
    ) {
        parent::__construct($apiResultFactory, $configHelper, $encoder, $decoder);
    }

    /**
     * @param array $order
     * @return ApiResult
     * @throws \Exception
     */
    public function submitPreparedOrder($order): ApiResult
    {
        $result = $this->postRequest(self::ACTION_BASE_URL, $order);
        if ($result->getHttpCode() !== 201) {
            throw new \Exception(sprintf("failed to submit order"));
        }

        return $result;
    }
}
