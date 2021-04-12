<?php
namespace Spod\Sync\Subscriber\Webhook\Article;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\CrudManager\ProductManager;
use Spod\Sync\Model\Mapping\WebhookEvent;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

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
        Registry $registry,
        WebhookEventRepository $webhookEventRepository,
        StatusHelper $statusHelper,
        SpodLoggerInterface $logger
    ) {
        $this->apiResultFactory = $apiResultFactory;
        $this->encoder = $encoder;
        $this->logger = $logger;
        $this->productManager = $productManager;
        $this->registry = $registry;

        parent::__construct($webhookEventRepository, $statusHelper, $logger);
    }

    public function execute(Observer $observer)
    {
        $webhookEvent = $this->getWebhookEventFromObserver($observer);

        if ($this->isObserverResponsible($webhookEvent)) {
            // decode and extract only article portion
            $payload = $webhookEvent->getDecodedPayload();
            $articleData = $payload->data->article;

            try {
                // put back json encoded article data
                $apiResult = $this->apiResultFactory->create();
                $jsonArticleData = $this->encoder->encodePayload($articleData);
                $apiResult->setPayload($jsonArticleData);

                $this->setAreaSecure();
                $this->productManager->updateProduct($apiResult);
                $this->setEventProcessed($webhookEvent);
            } catch (\Exception $e) {
                $this->logger->logError("article updated", $e->getMessage());
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
