<?php

declare(strict_types=1);

namespace Spod\Sync\Subscriber\Webhook\Article;

use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\CrudManager\ProductManager;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

/**
 * Event handler which processes the
 * Article.added webhook events.
 *
 */
class Added extends BaseSubscriber
{
    /** @var ApiResultFactory  */
    private $apiResultFactory;

    /** @var ProductManager  */
    private $productManager;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        ProductManager $productManager
    ) {
        $this->apiResultFactory = $apiResultFactory;
        $this->productManager = $productManager;
    }

    protected function processWebhookEvent(Webhook $webhookEvent): void
    {
        // decode and extract only article portion
        $payload = $webhookEvent->getDecodedPayload();
        $articleData = $payload->data->article;

        // put back json encoded article data
        $apiResult = $this->apiResultFactory->create();
        $apiResult->setPayload($articleData);

        $this->productManager->createOptionValues($articleData);
        $this->productManager->createProduct($apiResult);
    }
}
