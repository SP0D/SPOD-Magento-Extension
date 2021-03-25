<?php

namespace Spod\Sync\Model\CrudManager;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class OrderManager
{
    /** @var OrderRepository */
    private $orderRepository;
    /** @var SearchCriteriaBuilder  */
    private $searchCriteriaBuilder;

    public function __construct(
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param $spodOrderId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function completeOrder($spodOrderId)
    {
        $order = $this->getOrderBySpodOrderId($spodOrderId);
        if ($order->getState() !== Order::STATE_COMPLETE) {
            $this->setOrderAsComplete($order);
        }
    }

    protected function getOrderBySpodOrderId($spodOrderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('spod_order_id', $spodOrderId, 'eq')->create();
        $searchResults = $this->orderRepository->getList($searchCriteria);
        $orders = $searchResults->getItems();

        foreach ($orders as $order) {
            return $order;
        }

        throw new \Exception('SPOD Order Id not found');
    }

    /**
     * @param OrderInterface $order
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setOrderAsComplete(OrderInterface $order): void
    {
        $order->setState(Order::STATE_COMPLETE);
        $order->setStatus(Order::STATE_COMPLETE);
        $this->orderRepository->save($order);
    }


}
