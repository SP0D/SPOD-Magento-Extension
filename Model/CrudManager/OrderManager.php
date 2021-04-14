<?php

namespace Spod\Sync\Model\CrudManager;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Spod\Sync\Api\SpodLoggerInterface;

class OrderManager
{
    /** @var SpodLoggerInterface */
    private $logger;
    /** @var OrderRepository */
    private $orderRepository;
    /** @var SearchCriteriaBuilder  */
    private $searchCriteriaBuilder;

    public function __construct(
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SpodLoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param $spodOrderId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function cancelOrder($spodOrderId)
    {
        $order = $this->getOrderBySpodOrderId($spodOrderId);
        $order->setSpodCancelled(true);
        $order->cancel();
        $this->orderRepository->save($order);
        $this->logger->logDebug(sprintf("cancelled order", $order->getId()));
    }

    /**
     * @param $spodOrderId
     * @return OrderInterface
     * @throws \Exception
     */
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
     * @param $spodOrderId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function completeOrder($spodOrderId)
    {
        $order = $this->getOrderBySpodOrderId($spodOrderId);
        $order->setState(Order::STATE_COMPLETE)->setStatus(Order::STATE_COMPLETE);
        $this->orderRepository->save($order);
        $this->logger->logDebug(sprintf("completed order %d", $order->getId()));
    }
}
