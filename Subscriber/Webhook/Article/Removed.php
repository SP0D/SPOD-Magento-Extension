<?php
namespace Spod\Sync\Subscriber\Webhook\Article;

use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\CrudManager\ProductManager;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Model\Mapping\WebhookEvent;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

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
        SpodLoggerInterface $logger,
        WebhookEventRepository $webhookEventRepository,
        StatusHelper $statusHelper
    ) {
        $this->productManager = $productManager;
        $this->registry = $registry;
        $this->logger = $logger;

        parent::__construct($webhookEventRepository, $statusHelper);
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
