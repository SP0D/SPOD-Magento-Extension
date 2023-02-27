<?php

declare(strict_types=1);

namespace Spod\Sync\Subscriber\Webhook\Article;

use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\ApiReader\ArticleHandler;
use Spod\Sync\Model\CrudManager\ProductManager;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

/**
 * Event handler which processes the
 * initial article sync.
 *
 * @package Spod\Sync\Subscriber\Webhook\Article
 */
class InitialSync extends BaseSubscriber
{
    /** @var ArticleHandler */
    private $articleHandler;

    /** @var ProductManager  */
    private $productManager;

    /** @var StatusHelper */
    private $statusHelper;

    public function __construct(
        ArticleHandler $articleHandler,
        ProductManager $productManager,
        StatusHelper $statusHelper
    ) {
        $this->articleHandler = $articleHandler;
        $this->productManager = $productManager;
        $this->statusHelper = $statusHelper;
    }

    protected function processWebhookEvent(Webhook $webhookEvent): void
    {
        $articleResult = $this->articleHandler->getAllArticles();

        $this->statusHelper->setInitialSyncStartDate();
        $this->productManager->createAllProducts($articleResult);
        $this->statusHelper->setInitialSyncEndDate();
        $this->statusHelper->setLastsyncDate();
    }
}
