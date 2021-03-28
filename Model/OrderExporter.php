<?php

namespace Spod\Sync\Model;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Directory\Model\RegionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Model\Mapping\ShippingType;

class OrderExporter
{
    /** @var ConfigHelper */
    private $configHelper;
    /** @var CustomerRepository */
    private $customerRepository;
    /** @var OrderRepository */
    private $orderRepository;
    /** @var RegionFactory */
    private $regionFactory;
    /** @var ShippingType */
    private $shippingTypeMapper;
    /** @var Item  */
    private $taxItem;

    public function __construct(
        ConfigHelper $configHelper,
        CustomerRepository $customerRepository,
        Item $taxItem,
        OrderRepository $orderRepository,
        RegionFactory $regionFactory,
        ShippingType $shippingTypeMapper
    ) {
        $this->configHelper = $configHelper;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->regionFactory = $regionFactory;
        $this->shippingTypeMapper = $shippingTypeMapper;
        $this->taxItem = $taxItem;
    }

    public function prepareOrder(OrderRecord $order)
    {
        $magentoOrder = $this->getMagentoOrderById($order);
        $preparedOrder = [];

        //
        $preparedOrder['orderItems'] = $this->prepareOrderItems($magentoOrder);
        $preparedOrder['billingAddress'] = $this->prepareBillingAddress($magentoOrder);
        $preparedOrder['shipping'] = $this->prepareShipping($magentoOrder);

        // detail information
        $preparedOrder['phone'] = $this->preparePhone($magentoOrder);
        $preparedOrder['email'] = $this->prepareEmail($magentoOrder);
        $preparedOrder['externalOrderReference'] = $this->prepareOrderReference($magentoOrder);
        $preparedOrder['state'] = 'NEW';
        $preparedOrder['customerTaxType'] = $this->prepareCustomerTaxType($magentoOrder);

        return $preparedOrder;
    }

    private function prepareOrderItems(OrderInterface $order)
    {
        $orderedItems = $order->getAllItems();
        $items = [];

        foreach ($orderedItems as $orderedItem) {
            if ($orderedItem->getProduct()->getTypeId() != 'simple') {
                continue;
            }

            $items[] = [
                'sku' => $orderedItem->getProduct()->getSku(),
                'quantity' => intval($orderedItem->getQtyOrdered()),
                'externalOrderItemReference' => $orderedItem->getId(),
                'customerPrice' => [
                    'amount' => floatval($orderedItem->getParentItem()->getRowTotal()),
                    'taxRate' => floatval($orderedItem->getParentItem()->getTaxPercent()),
                    'taxAmount' => floatval($orderedItem->getParentItem()->getTaxAmount()),
                    'currency' => $order->getStore()->getCurrentCurrency()->getCurrencyCode()
                ]
            ];
        }

        return $items;
    }

    private function prepareShipping(OrderInterface $order)
    {
        $shipping = [];

        $shipping['address'] = $this->prepareShippingAddress($order);
        $shipping['preferredType'] = $this->shippingTypeMapper->getShippingTypeForOrder($order);
        $shipping['customerPrice'] = $this->prepareShippingPrice($order);
        $shipping['fromAddress'] = $this->prepareFromAddress($order);

        return $shipping;
    }

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

    private function preparePhone(OrderInterface $order)
    {
        /** @var Address $billing */
        $billing = $order->getBillingAddress();
        if ($billing) {
            return $billing->getTelephone();
        }

        return '';
    }

    private function prepareEmail(OrderInterface $order)
    {
        return $order->getCustomerEmail();
    }

    private function prepareOrderReference(OrderInterface $order)
    {
        return $order->getIncrementId();
    }

    private function prepareCustomerTaxType(OrderInterface $order)
    {
        return "NOT_TAXABLE";
    }

    private function getMagentoOrderById(OrderRecord $spodOrder): OrderInterface
    {
        return $this->orderRepository->get($spodOrder->getOrderId());
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function prepareShippingPrice(OrderInterface $order): array
    {
        $shippingPrice = [];

        $shippingPrice['amount'] = floatval($order->getShippingAmount());
        $shippingPrice['taxRate'] = floatval($this->getShippingTaxRate($order));
        $shippingPrice['taxAmount'] = floatval($order->getShippingTaxAmount());
        $shippingPrice['currency'] = $order->getStore()->getCurrentCurrency()->getCurrencyCode();

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
        $fromAddress = [];
        $fromAddress['company'] = $this->configHelper->getConfigValue('general/store_information/name', $order->getStoreId());
        $fromAddress['firstName'] = '';
        $fromAddress['lastName'] = '';
        $fromAddress['street'] = $this->configHelper->getConfigValue('general/store_information/street_line1', $order->getStoreId());
        $fromAddress['streetAnnex'] = $this->configHelper->getConfigValue('general/store_information/street_line2', $order->getStoreId());
        $fromAddress['city'] = $this->configHelper->getConfigValue('general/store_information/city', $order->getStoreId());
        $fromAddress['country'] = $this->configHelper->getConfigValue('general/store_information/country_id', $order->getStoreId());
        $fromAddress['state'] = $this->getOrderRegionData($this->configHelper->getConfigValue('general/store_information/region_id', $order->getStoreId()));
        $fromAddress['zipCode'] = $this->configHelper->getConfigValue('general/store_information/postcode', $order->getStoreId());

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
