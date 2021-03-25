<?php
namespace Spod\Sync\Subscriber\Webhook\Article;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\Observer;
use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\ProductManager;
use Spod\Sync\Model\Mapping\WebhookEvent;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

class Added extends BaseSubscriber
{
    protected $event = WebhookEvent::EVENT_ARTICLE_ADDED;

    /** @var ApiResultFactory  */
    private $apiResultFactory;
    /** @var PayloadEncoder  */
    private $encoder;
    /** @var ProductManager  */
    private $productManager;
    /** @var SpodLoggerInterface  */
    private $logger;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        PayloadEncoder $encoder,
        ProductManager $productManager,
        SpodLoggerInterface $logger)
    {
        $this->apiResultFactory = $apiResultFactory;
        $this->encoder = $encoder;
        $this->productManager = $productManager;
        $this->logger = $logger;
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
                $this->setEventFailed($webhookEvent);
                $this->logger->logError($e->getMessage());
            }
        }

        return $this;
    }
}
