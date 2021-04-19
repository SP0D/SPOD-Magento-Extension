<?php
namespace Spod\Sync\Subscriber\Webhook;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Model\Webhook;

/**
 * Base class for webhook handler classes.
 *
 * @package Spod\Sync\Subscriber\Webhook
 */
abstract class BaseSubscriber implements ObserverInterface
{
    protected $event = false;

    /** @var WebhookEventRepository */
    protected $webhookEventRepository;
    /** @var StatusHelper */
    protected $statusHelper;
    /** @var SpodLoggerInterface  */
    protected $logger;

    public function __construct(
        WebhookEventRepository $webhookEventRepository,
        StatusHelper $statusHelper,
        SpodLoggerInterface $logger
    ) {
        $this->webhookEventRepository = $webhookEventRepository;
        $this->statusHelper = $statusHelper;
        $this->logger = $logger;
    }

    public function setEventProcessed(Webhook $webhookEvent)
    {
        $this->statusHelper->setLastsyncDate();
        $webhookEvent->setStatus(QueueStatus::STATUS_PROCESSED);
        $webhookEvent->setProcessedAt(new \DateTime());
        $this->webhookEventRepository->save($webhookEvent);
    }

    public function setEventFailed(Webhook $webhookEvent)
    {
        $webhookEvent->setStatus(QueueStatus::STATUS_ERROR);
        $webhookEvent->setProcessedAt(new \DateTime());
        $this->webhookEventRepository->save($webhookEvent);
    }

    public function getWebhookEventFromObserver(Observer $observer): Webhook
    {
        return $observer->getData('webhook_event');
    }

    protected function isObserverResponsible($webhookEvent)
    {
        if ($webhookEvent->getEventType() == $this->event) {
            return true;
        } else {
            return false;
        }
    }
    public function execute(Observer $observer)
    {
    }
}
