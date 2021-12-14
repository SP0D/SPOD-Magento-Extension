<?php

declare(strict_types=1);

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
    /** @var null|string */
    protected $event = null;

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

    public function setEventProcessed(Webhook $webhookEvent): void
    {
        $this->statusHelper->setLastsyncDate();
        $webhookEvent->setStatus(QueueStatus::STATUS_PROCESSED);
        $webhookEvent->setProcessedAt(new \DateTimeImmutable());
        $this->webhookEventRepository->save($webhookEvent);
    }

    public function setEventFailed(Webhook $webhookEvent): void
    {
        $webhookEvent->setStatus(QueueStatus::STATUS_ERROR);
        $webhookEvent->setProcessedAt(new \DateTimeImmutable());
        $this->webhookEventRepository->save($webhookEvent);
    }

    public function getWebhookEventFromObserver(Observer $observer): Webhook
    {
        return $observer->getData('webhook_event');
    }

    protected function isObserverResponsible(Webhook $webhookEvent): bool
    {
        return $webhookEvent->getEventType() === $this->event;
    }
    public function execute(Observer $observer)
    {
    }
}
