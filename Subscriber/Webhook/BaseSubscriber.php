<?php
namespace Spod\Sync\Subscriber\Webhook;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\Mapping\QueueStatus;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Model\Webhook;

abstract class BaseSubscriber implements ObserverInterface
{
    protected $event = false;

    /** @var WebhookEventRepository */
    protected $webhookEventRepository;

    /** @var StatusHelper */
    protected $statusHelper;

    public function __construct(
        WebhookEventRepository $webhookEventRepository,
        StatusHelper $statusHelper
    ) {
        $this->webhookEventRepository = $webhookEventRepository;
        $this->statusHelper = $statusHelper;
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
