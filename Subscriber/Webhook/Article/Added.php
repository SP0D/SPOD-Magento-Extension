<?php
namespace Spod\Sync\Subscriber\Webhook\Article;

use Magento\Framework\Event\Observer;
use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\CrudManager\ProductManager;
use Spod\Sync\Model\Mapping\WebhookEvent;
use Spod\Sync\Model\Repository\WebhookEventRepository;
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

    public function __construct(
        ApiResultFactory $apiResultFactory,
        PayloadEncoder $encoder,
        ProductManager $productManager,
        SpodLoggerInterface $logger,
        WebhookEventRepository $webhookEventRepository,
        StatusHelper $statusHelper
    ) {
        $this->apiResultFactory = $apiResultFactory;
        $this->encoder = $encoder;
        $this->productManager = $productManager;

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

                $this->productManager->createOptionValues($articleData);
                $this->productManager->createProduct($apiResult);
                $this->setEventProcessed($webhookEvent);
            } catch (\Exception $e) {
                $this->setEventFailed($webhookEvent);
                $this->logger->logError("article added", $e->getMessage(), $e->getTraceAsString());
            }
        }

        return $this;
    }
}
