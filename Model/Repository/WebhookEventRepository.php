<?php

namespace Spod\Sync\Model\Repository;

use Spod\Sync\Model\ResourceModel\Webhook as WebhookResource;
use Spod\Sync\Model\Webhook;

/**
 * Custom repository which handles saving of
 * webhook models by using the resource model.
 *
 * @package Spod\Sync\Model\Repository
 */
class WebhookEventRepository
{
    /** @var WebhookResource  */
    private $webhookResource;

    public function __construct(
        WebhookResource $webhookResource
    ) {
        $this->webhookResource = $webhookResource;
    }

    public function save(Webhook $webhook)
    {
        $this->webhookResource->save($webhook);
    }
}
