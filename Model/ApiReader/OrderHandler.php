<?php

declare(strict_types=1);

namespace Spod\Sync\Model\ApiReader;

use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Model\ApiResult;
use Spod\Sync\Model\ApiResultFactory;

/**
 * Contains the methods which handle
 * all requests to the /orders resource.
 *
 * @package Spod\Sync\Model\ApiReader
 */
class OrderHandler extends AbstractHandler
{
    const ACTION_BASE_URL = '/orders';

    /** @var SpodLoggerInterface */
    private $logger;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        ConfigHelper $configHelper,
        SpodLoggerInterface $logger
    ) {
        $this->logger = $logger;
        parent::__construct($apiResultFactory, $configHelper);
    }

    /**
     * @param array $order
     * @return ApiResult
     */
    public function submitPreparedOrder(array $order): ApiResult
    {
        return $this->postRequest(self::ACTION_BASE_URL, $order);
    }

    /**
     * @throws \Exception
     */
    public function cancelOrder($orderId): bool
    {
        $action = sprintf("%s/%d/cancel", self::ACTION_BASE_URL, $orderId);
        $result = $this->postRequest($action);

        $this->logger->logDebug("cancelling order");
        if ($result->getHttpCode() == 404) {
            throw new \Exception(sprintf("Order %d not found in SPOD.", $orderId));
        }

        return $result->getHttpCode() === 202;
    }

    public function updateOrder(int $spodOrderId, array $order): ApiResult
    {
        $action = sprintf("%s/%d", self::ACTION_BASE_URL, $spodOrderId);
        $this->logger->logDebug('sending PUT request');
        $result = $this->putRequest($action, $order);

        if ($result->getHttpCode() !== 201) {
            $this->logger->logError(
                "update order",
                sprintf("updating order failed, httpStatus %s", $result->getHttpCode()),
                json_encode($result->getPayload())
            );
            throw new \Exception("failed to update order");
        }

        return $result;
    }
}
