<?php

namespace Spod\Sync\Model\Mapping;

use Magento\Sales\Api\Data\OrderInterface;
use Spod\Sync\Helper\ConfigHelper;

/**
 * Mapping of SPOD shipping types.
 *
 * @package Spod\Sync\Model\Mapping
 */
class ShippingType
{
    const SHIPPING_STANDARD = 'STANDARD';
    const SHIPPING_PREMIUM = 'PREMIUM';
    const SHIPPING_EXPRESS = 'EXPRESS';

    private ConfigHelper $configHelper;

    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    public function getShippingTypeForOrder(OrderInterface $order)
    {
        $methodCode = $order->getShippingMethod();
        $premiumMethods = $this->configHelper->getPremiumShippingMapping();
        $expressMethods = $this->configHelper->getExpressShippingMapping();

        // default value
        $shippingType = self::SHIPPING_STANDARD;

        // premium overrides standard
        if (in_array($methodCode, $premiumMethods)) {
            $shippingType = self::SHIPPING_PREMIUM;
        }

        // express mapping overrides premium
        if (in_array($methodCode, $expressMethods)) {
            $shippingType = self::SHIPPING_EXPRESS;
        }

        return $shippingType;
    }
}
