<?php
namespace Spod\Sync\Subscriber\Article;

use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\ProductManager;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Model\Mapping\WebhookEvent;
use Spod\Sync\Subscriber\BaseSubscriber;

class Removed extends BaseSubscriber
{
    protected $event = WebhookEvent::EVENT_ARTICLE_REMOVED;

    /** @var ProductManager  */
    private $productManager;

    /** @var SpodLoggerInterface  */
    private $logger;

    /** @var Registry  */
    private $registry;

    public function __construct(
        ProductManager $productManager,
        Registry $registry,
        SpodLoggerInterface $logger
    ) {
        $this->productManager = $productManager;
        $this->registry = $registry;
        $this->logger = $logger;
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
                $this->setEventFailed($webhookEvent);
                $this->logger->logError($e->getMessage());
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


}
