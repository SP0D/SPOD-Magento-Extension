<?php

declare(strict_types=1);

namespace Spod\Sync\Subscriber\Webhook\Article;

use Magento\Framework\Registry;
use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\CrudManager\ProductManager;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

/**
 * Event handler which processes the
 * Article.updated webhook events.
 *
 * @package Spod\Sync\Subscriber\Webhook\Article
 */
class Updated extends BaseSubscriber
{
    private $apiResultFactory;

    private $productManager;

    private $registry;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        ProductManager $productManager,
        Registry $registry
    ) {
        $this->apiResultFactory = $apiResultFactory;
        $this->productManager = $productManager;
        $this->registry = $registry;
    }

    protected function processWebhookEvent(Webhook $webhookEvent): void
    {
        // decode and extract only article portion
        $payload = $webhookEvent->getDecodedPayload();
        $articleData = $payload->data->article;

        // put back json encoded article data
        $apiResult = $this->apiResultFactory->create();
        $apiResult->setPayload($articleData);

        $this->setAreaSecure();
        $this->productManager->updateProduct($apiResult);
    }

    private function setAreaSecure()
    {
        if (!$this->registry->registry('isSecureArea')) {
            $this->registry->register('isSecureArea', true);
        }
    }
}
