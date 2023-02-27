<?php

declare(strict_types=1);

namespace Spod\Sync\Subscriber\Webhook;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Spod\Sync\Model\Webhook;

/**
 * Base class for webhook handler classes.
 */
abstract class BaseSubscriber implements ObserverInterface
{
    public function getWebhookEventFromObserver(Observer $observer): Webhook
    {
        return $observer->getData('webhook_event');
    }

    final public function execute(Observer $observer)
    {
        $webhookEvent = $this->getWebhookEventFromObserver($observer);
        $this->processWebhookEvent($webhookEvent);
    }

    abstract protected function processWebhookEvent(Webhook $webhookEvent): void;
}
