<?php
namespace Spod\Sync\Subscriber;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Model\WebhookEvent;
use Spod\Sync\Model\QueueStatus;

abstract class BaseSubscriber implements ObserverInterface
{
    protected $event = false;

    public function setEventProcessed(Webhook $webhookEvent)
    {
        $webhookEvent->setStatus(QueueStatus::STATUS_PROCESSED);
        $webhookEvent->setProcessedAt(new \DateTime());
        $webhookEvent->save();
    }

    public function setEventFailed(Webhook $webhookEvent)
    {
        $webhookEvent->setStatus(QueueStatus::STATUS_ERROR);
        $webhookEvent->setProcessedAt(new \DateTime());
        $webhookEvent->save();
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
