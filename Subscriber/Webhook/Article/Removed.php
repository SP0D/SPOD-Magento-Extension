<?php

declare(strict_types=1);

namespace Spod\Sync\Subscriber\Webhook\Article;

use Magento\Framework\Registry;
use Spod\Sync\Model\CrudManager\ProductManager;
use Spod\Sync\Model\Webhook;
use Spod\Sync\Subscriber\Webhook\BaseSubscriber;

/**
 * Event handler which processes the
 * Article.removed webhook events.
 *
 * @package Spod\Sync\Subscriber\Webhook\Article
 */
class Removed extends BaseSubscriber
{
    /** @var ProductManager  */
    private $productManager;

    /** @var Registry  */
    private $registry;

    public function __construct(
        ProductManager $productManager,
        Registry $registry
    ) {
        $this->productManager = $productManager;
        $this->registry = $registry;
    }

    protected function processWebhookEvent(Webhook $webhookEvent): void
    {
        $this->setAreaSecure();
        $this->removeProduct($webhookEvent);
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
