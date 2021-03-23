<?php
namespace Spod\Sync\Subscriber\Article;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\ProductManager;
use Spod\Sync\Model\WebhookEvent;
use Spod\Sync\Subscriber\BaseSubscriber;

class Updated extends BaseSubscriber
{
    protected $event = WebhookEvent::EVENT_ARTICLE_UPDATED;

    private $apiResultFactory;
    private $encoder;
    private $productManager;
    private $registry;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        PayloadEncoder $encoder,
        ProductManager $productManager,
        Registry $registry
    ) {
        $this->apiResultFactory = $apiResultFactory;
        $this->encoder = $encoder;
        $this->productManager = $productManager;
        $this->registry = $registry;
    }

    public function execute(Observer $observer)
    {
        $webhookEvent = $this->getWebhookEventFromObserver($observer);

        if ($this->isObserverResponsible($webhookEvent)) {
            $payload = $webhookEvent->getDecodedPayload();
            $articleData = $payload->data->article;

            try {
                $apiResult = $this->apiResultFactory->create();
                $apiResult->setPayload($this->encoder->encodePayload($articleData));

                $this->setAreaSecure();
                $this->productManager->updateProduct($apiResult);
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
}
