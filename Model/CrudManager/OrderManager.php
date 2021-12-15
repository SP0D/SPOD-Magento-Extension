<?php

declare(strict_types=1);

namespace Spod\Sync\Model\CrudManager;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\OrderRepository;
use Spod\Sync\Api\SpodLoggerInterface;

/**
 * Handles local changes to Magento orders which
 * are triggered by webhook requests (cancel / complete).
 *
 * @package Spod\Sync\Model\CrudManager
 */
class OrderManager
{
    /** @var SpodLoggerInterface */
    private $logger;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var SearchCriteriaBuilder  */
    private $searchCriteriaBuilder;

    /** @var CreditmemoFactory */
    private $creditmemoFactory;

    /** @var CreditmemoManagementInterface */
    private $creditmemoManagement;

    public function __construct(
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoManagementInterface $creditmemoManagement,
        SpodLoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoManagement = $creditmemoManagement;
    }

    public function cancelOrder(int $spodOrderId): void
    {
        /** @var OrderInterface|Order $order */
        $order = $this->getOrderBySpodOrderId($spodOrderId);
        $order->setSpodCancelled(true);
        $order->addCommentToStatusHistory('Order was canceled on SPOD.', false, false);
        if ($order->canCancel()) {
            $order->cancel();
        } elseif ($order->canCreditmemo()) {
            $qtys = [];
            foreach ($order->getItems() as $orderItem) {
                if (!$orderItem->getData('spod_order_item_id')) {
                    continue;
                }
                $obj = $orderItem;
                if ($orderItem->getParentItem()) {
                    $obj = $orderItem->getParentItem();
                }
                $qtys[$obj->getId()] = $obj->getQtyInvoiced();
            }

            $creditmemo = $this->creditmemoFactory->createByOrder($order, ['qtys' => $qtys, 'shipping_amount' => 0.0]);
            $creditmemo->addComment('Creditmemo was created because of canceled SPOD order. Shipping amount to refund was set to 0.', false, false);
            $order->addCommentToStatusHistory(
                'Creditmemo was created for the canceled SPOD order',
                false,
                false
            );
            $this->creditmemoManagement->refund($creditmemo, false);
        }
        $this->logger->logDebug(sprintf("cancelled order #%s", $order->getIncrementId()));
    }

    protected function getOrderBySpodOrderId(int $spodOrderId): OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('spod_order_id', $spodOrderId, 'eq')->create();
        $searchResults = $this->orderRepository->getList($searchCriteria);
        $orders = $searchResults->getItems();

        foreach ($orders as $order) {
            return $order;
        }

        throw new \Exception('SPOD Order Id not found');
    }

    public function completeOrder(int $spodOrderId): void
    {
        /** @var OrderInterface|Order $order */
        $order = $this->getOrderBySpodOrderId($spodOrderId);
        $order->addCommentToStatusHistory(
            'Order was processed on SPOD.',
            false,
            false
        );
        $this->orderRepository->save($order);
        $this->logger->logDebug(sprintf("order processed #%d", $order->getIncrementId()));
    }
}
