<?php
namespace Spod\Sync\Subscriber\Webhook\Article;

use Magento\Framework\Event\Observer;
use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\ApiReader\ArticleHandler;
use Spod\Sync\Model\CrudManager\ProductManager;
use Spod\Sync\Model\Mapping\WebhookEvent;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

class InitialSync extends BaseSubscriber
{
    protected $event = WebhookEvent::EVENT_ARTICLE_INITALSYNC;

    /** @var ArticleHandler */
    private $articleHandler;
    /** @var PayloadEncoder  */
    private $encoder;
    /** @var ProductManager  */
    private $productManager;
    /** @var SpodLoggerInterface  */
    private $logger;

    public function __construct(
        ArticleHandler $articleHandler,
        PayloadEncoder $encoder,
        ProductManager $productManager,
        SpodLoggerInterface $logger,
        StatusHelper $statusHelper,
        WebhookEventRepository $webhookEventRepository
    ) {
        $this->articleHandler = $articleHandler;
        $this->encoder = $encoder;
        $this->productManager = $productManager;
        $this->logger = $logger;
        parent::__construct($webhookEventRepository, $statusHelper);
    }

    public function execute(Observer $observer)
    {
        $webhookEvent = $this->getWebhookEventFromObserver($observer);
        if ($this->isObserverResponsible($webhookEvent)) {
            try {
                $articleResult = $this->articleHandler->getAllArticles();

                $this->statusHelper->setInitialSyncStartDate();
                $this->productManager->createAllProducts($articleResult);
                $this->statusHelper->setInitialSyncEndDate();
                $this->statusHelper->setLastsyncDate();

                $this->setEventProcessed($webhookEvent);

            } catch (\Exception $e) {
                $this->setEventFailed($webhookEvent);
                $this->logger->logError("initial sync", $e->getMessage());
            }
        }

        return $this;
    }
}
