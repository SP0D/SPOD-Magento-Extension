<?php
namespace Spod\Sync\Subscriber;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Model\WebhookStatus;

class BaseSubscriber implements ObserverInterface
{
    public function setEventProcessed(Webhook $webhookEvent)
    {
        $webhookEvent->setStatus(WebhookStatus::WEBHOOK_STATUS_PROCESSED);
        $webhookEvent->setProcessedAt(new \DateTime());
        $webhookEvent->save();
    }

    public function setEventFailed(Webhook $webhookEvent)
    {
        $webhookEvent->setStatus(WebhookStatus::WEBHOOK_STATUS_ERROR);
        $webhookEvent->setProcessedAt(new \DateTime());
        $webhookEvent->save();
    }

    public function execute(Observer $observer)
    {
    }
}
