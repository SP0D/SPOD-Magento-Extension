<?php
namespace Spod\Sync\Subscriber\Article;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\Observer;
use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\ProductManager;
use Spod\Sync\Model\WebhookEvent;
use Spod\Sync\Subscriber\BaseSubscriber;

class Added extends BaseSubscriber
{
    protected $event = WebhookEvent::EVENT_ARTICLE_ADDED;

    private $apiResultFactory;
    private $encoder;
    private $productManager;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        PayloadEncoder $encoder,
        ProductManager $productManager)
    {
        $this->apiResultFactory = $apiResultFactory;
        $this->encoder = $encoder;
        $this->productManager = $productManager;
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

                $this->productManager->createProduct($apiResult);
                $this->setEventProcessed($webhookEvent);
            } catch (\Exception $e) {
                // TODO: log
                $this->setEventFailed($webhookEvent);
            }
        }

        return $this;
    }
}
