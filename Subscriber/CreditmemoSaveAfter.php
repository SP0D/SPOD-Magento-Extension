<?php

namespace Spod\Sync\Subscriber;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Creditmemo;

class CreditmemoSaveAfter implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /** @var CreditmemoInterface|Creditmemo $creditmemo */
        $creditmemo = $observer->getCreditmemo();
        $order = $creditmemo->getOrder();

        if (!$order->getSpodOrderId() || $order->getSpodCancelled()) {
            return;
        }

        $spodOrderItems = array_filter(
            $order->getItems(),
            function (OrderItemInterface $orderItem) {
                return $orderItem->getData('spod_order_item_id') && $orderItem->getQtyToInvoice() == 0;
            }
        );

        if (count($spodOrderItems)) {
            $creditmemo->addComment(
                'Please, contact SPOD customer support. Current order has SPOD products and was synced to SPOD system.'
            );
            $order->addCommentToStatusHistory(
                sprintf('Please, contact SPOD customer service due to Creditmemo %s', $creditmemo->getIncrementId())
            );
        }
    }
}
