<?php

namespace Spod\Sync\Model\Repository;

use Spod\Sync\Model\ResourceModel\Webhook as WebhookResource;
use Spod\Sync\Model\Webhook;

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
