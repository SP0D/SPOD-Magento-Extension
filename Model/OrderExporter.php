<?php

declare(strict_types=1);

namespace Spod\Sync\Model;

use Magento\Customer\Model\Address;
use Magento\Directory\Model\RegionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Model\Mapping\ShippingType;

/**
 * This model takes a Magento 2 order
 * and exports the required data structure
 * which then gets encoded.
 *
 * @package Spod\Sync\Model
 */
class OrderExporter
{
    const ORDER_REFERENCE_PREFIX = 'MAGE';

    /** @var ConfigHelper */
    private $configHelper;

    /** @var RegionFactory */
    private $regionFactory;

    /** @var ShippingType */
    private $shippingTypeMapper;

    /** @var Item  */
    private $taxItem;

    public function __construct(
        ConfigHelper $configHelper,
        Item $taxItem,
        RegionFactory $regionFactory,
        ShippingType $shippingTypeMapper
    ) {
        $this->configHelper = $configHelper;
        $this->regionFactory = $regionFactory;
        $this->shippingTypeMapper = $shippingTypeMapper;
        $this->taxItem = $taxItem;
    }

    /**
     * Public method called from outside that returns the
     * prepared order as array structure.
     *
     * @param OrderInterface $magentoOrder
     * @return array
     */
    public function prepareOrder(OrderInterface $magentoOrder): array
    {
        $orderItems = $this->prepareOrderItems($magentoOrder);

        if (0 === count($orderItems)) {
            throw new \Exception('Magento Order has no SPOD products.');
        }

        $spodOrder = [];
        $spodOrder['orderItems'] = $orderItems;
        $spodOrder['shipping'] = $this->prepareShipping($magentoOrder);
        $spodOrder['billingAddress'] = $this->prepareBillingAddress($magentoOrder);
        $spodOrder['phone'] = $this->preparePhone($magentoOrder);
        $spodOrder['email'] = $this->prepareEmail($magentoOrder);
        $spodOrder['externalOrderReference'] = $this->prepareOrderReference($magentoOrder);
        $spodOrder['state'] = 'NEW';
        $spodOrder['customerTaxType'] = $this->prepareCustomerTaxType($magentoOrder);

        return $spodOrder;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function prepareOrderItems(OrderInterface $order): array
    {
        $items = [];
        foreach ($order->getAllItems() as $orderedItem) {
            $isSimple = $orderedItem->getProduct()->getTypeId() == 'simple';
            $isSpodProduct = (bool) $orderedItem->getProduct()->getSpodProduct();
            if (!$isSimple || !$isSpodProduct) {
                continue;
            }

            $items[] = [
                'sku' => $orderedItem->getProduct()->getSku(),
                'quantity' => intval($orderedItem->getQtyInvoiced()),
                'externalOrderItemReference' => $orderedItem->getId(),
                'customerPrice' => [
                    'amount' => floatval($orderedItem->getParentItem()->getRowInvoiced()),
                    'taxRate' => floatval($orderedItem->getParentItem()->getTaxPercent()),
                    'taxAmount' => floatval($orderedItem->getParentItem()->getTaxAmount()),
                    'currency' => $order->getOrderCurrencyCode()
                ]
            ];
        }

        return $items;
    }

    private function prepareShipping(OrderInterface $order): array
    {
        $shipping = [];

        $shipping['address'] = $this->prepareShippingAddress($order);
        $shipping['fromAddress'] = $this->prepareFromAddress($order);
        $shipping['preferredType'] = $this->shippingTypeMapper->getShippingTypeForOrder($order);
        $shipping['customerPrice'] = $this->prepareShippingPrice($order);

        return $shipping;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function prepareBillingAddress(OrderInterface $order)
    {
        /** @var Address $billingAddress */
        $billingAddress = $order->getBillingAddress();

        $billing = [];

        if ($billingAddress->getCompany()) {
            $billing['company'] = $billingAddress->getCompany();
        } else {
            $billing['company'] = '';
        }

        $billing['firstName'] = $billingAddress->getFirstname();
        $billing['lastName'] = $billingAddress->getLastname();

        $street = $billingAddress->getStreet();
        if (isset($street[0])) {
            $billing['street'] = $street[0];
        }
        if (isset($street[1])) {
            $billing['streetAnnex'] = $street[1];
        }

        $billing['city'] = $billingAddress->getCity();
        $billing['country'] = $billingAddress->getCountryId();

        if ($billingAddress->getRegionCode()) {
            $billing['state'] = $billingAddress->getRegionCode();
        } else {
            $billing['state'] = '';
        }
        $billing['zipCode'] = $billingAddress->getPostcode();

        return $billing;
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    private function preparePhone(OrderInterface $order)
    {
        /** @var Address $billing */
        $billing = $order->getBillingAddress();
        if ($billing) {
            return $billing->getTelephone();
        }

        return '';
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    private function prepareEmail(OrderInterface $order)
    {
        return $order->getCustomerEmail();
    }

    /**
     * @param OrderInterface $order
     * @return string|null
     */
    private function prepareOrderReference(OrderInterface $order)
    {
        return sprintf('%s%s', self::ORDER_REFERENCE_PREFIX, $order->getIncrementId());
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    private function prepareCustomerTaxType(OrderInterface $order)
    {
        return "NOT_TAXABLE";
    }

    /**
     * @param OrderInterface $order
     * @param array $address
     * @return array
     */
    private function prepareShippingAddress(OrderInterface $order): array
    {
        $address = [];

        /** @var Address $shippingAddress */
        $shippingAddress = $order->getShippingAddress();

        if ($shippingAddress->getCompany()) {
            $address['company'] = $shippingAddress->getCompany();
        } else {
            $address['company'] = '';
        }

        $address['firstName'] = $shippingAddress->getFirstname();
        $address['lastName'] = $shippingAddress->getLastname();

        $street = $shippingAddress->getStreet();
        if (isset($street[0])) {
            $address['street'] = $street[0];
        }
        if (isset($street[1])) {
            $address['streetAnnex'] = $street[1];
        }

        $address['city'] = $shippingAddress->getCity();
        $address['country'] = $shippingAddress->getCountryId();

        if ($shippingAddress->getRegionCode()) {
            $address['state'] = $shippingAddress->getRegionCode();
        } else {
            $address['state'] = '';
        }

        $address['zipCode'] = $shippingAddress->getPostcode();
        return $address;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function prepareShippingPrice(OrderInterface $order): array
    {
        $shippingPrice = [];

        $shippingPrice['amount'] = floatval($order->getShippingAmount());
        $shippingPrice['taxRate'] = floatval($this->getShippingTaxRate($order));
        $shippingPrice['taxAmount'] = floatval($order->getShippingTaxAmount());
        $shippingPrice['currency'] = $order->getOrderCurrencyCode();

        return $shippingPrice;
    }

    /**
     * @param OrderInterface $order
     * @return int|mixed
     */
    private function getShippingTaxRate(OrderInterface $order)
    {
        $tax_items = $this->taxItem->getTaxItemsByOrderId($order->getId());
        if (!is_array($tax_items)) {
            return 0;
        }

        foreach ($tax_items as $item) {
            if ($item['taxable_item_type'] === 'shipping') {
                return $item['tax_percent'];
            }
        }

        return 0;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function prepareFromAddress(OrderInterface $order)
    {
        $companyName = $this->configHelper->getConfigValue('general/store_information/name', $order->getStoreId());
        $firstname = $this->configHelper->getFromFirstname();
        $lastname = $this->configHelper->getFromLastname();
        $street = $this->configHelper->getConfigValue('general/store_information/street_line1', $order->getStoreId());
        $streetAnnex = $this->configHelper->getConfigValue('general/store_information/street_line2', $order->getStoreId());
        $city = $this->configHelper->getConfigValue('general/store_information/city', $order->getStoreId());
        $country = $this->configHelper->getConfigValue('general/store_information/country_id', $order->getStoreId());
        $state = $this->getOrderRegionData($this->configHelper->getConfigValue('general/store_information/region_id', $order->getStoreId()));
        $zipCode = $this->configHelper->getConfigValue('general/store_information/postcode', $order->getStoreId());

        if ($street == null || $city == null || $country == null) {
            return null;
        }

        $fromAddress = [];
        $fromAddress['company'] = $companyName;
        $fromAddress['firstName'] = $firstname;
        $fromAddress['lastName'] = $lastname;
        $fromAddress['street'] = $street;
        $fromAddress['streetAnnex'] = $streetAnnex;
        $fromAddress['city'] = $city;
        $fromAddress['country'] = $country;
        $fromAddress['state'] = $state;
        $fromAddress['zipCode'] = $zipCode;

        return $fromAddress;
    }

    /**
     * @param $regionId
     * @return string
     */
    private function getOrderRegionData($regionId)
    {
        $region = $this->regionFactory->create()->load($regionId);
        return $region->getCode();
    }
}
