<?php
namespace Spod\Sync\Subscriber\Article;

use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Spod\Sync\Model\ProductManager;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Model\WebhookEvent;
use Spod\Sync\Subscriber\BaseSubscriber;

class Removed extends BaseSubscriber
{
    /** @var ProductManager  */
    private $productManager;

    /** @var Registry  */
    private $registry;

    public function __construct(
        ProductManager $productManager,
        Registry $registry
    ) {
        $this->productManager = $productManager;
        $this->registry = $registry;
    }

    public function execute(Observer $observer)
    {
        $webhookEvent = $this->getWebhookEventFromObserver($observer);
        if ($this->isObserverResponsible($webhookEvent)) {
            try {
                $this->setAreaSecure();
                $this->removeProduct($webhookEvent);
                $this->setEventProcessed($webhookEvent);
            } catch (\Exception $e) {
                // TODO: log
                $this->setEventFailed($webhookEvent);
            }
        }

        return $this;
    }

    private function setAreaSecure()
    {
        if (!$this->registry->registry('isSecureArea')) {
            $this->registry->register('isSecureArea', true);
        }
    }

    private function removeProduct(Webhook $webhookEvent)
    {
        $payloadObject = $webhookEvent->getDecodedPayload();
        $spodArticleId = $payloadObject->data->article->id;
        $this->productManager->deleteProductAndVariants($spodArticleId);
    }

    private function isObserverResponsible($webhookEvent)
    {
        if ($webhookEvent->getEventType() == WebhookEvent::EVENT_ARTICLE_REMOVED) {
            return true;
        } else {
            return false;
        }
    }
}
