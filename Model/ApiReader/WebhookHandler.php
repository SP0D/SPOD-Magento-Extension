<?php

namespace Spod\Sync\Model\ApiReader;

use Spod\Sync\Api\PayloadEncoder;
use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Helper\UrlGenerator;
use Spod\Sync\Model\ApiResult;
use Spod\Sync\Model\ApiResultFactory;
use Spod\Sync\Model\WebhookEvent;

class WebhookHandler extends AbstractHandler
{
    const ACTION_BASE_URL = '/subscriptions';

    /** @var UrlGenerator  */
    private $urlGenerator;

    public function __construct(
        ApiResultFactory $apiResultFactory,
        ConfigHelper $configHelper,
        PayloadEncoder $encoder,
        ResultDecoder $decoder,
        UrlGenerator $urlGenerator
    ) {
        $this->urlGenerator = $urlGenerator;
        parent::__construct($apiResultFactory, $configHelper, $encoder, $decoder);
    }

    /**
     * @throws \Exception
     */
    public function registerWebhooks()
    {
        $events = WebhookEvent::getAllEvents();

        foreach ($events as $eventType) {
            $this->addSubscription($eventType);
        }
    }

    /**
     * @param string $eventType
     * @throws \Exception
     */
    protected function addSubscription(string $eventType): void
    {
        $params = [
            'eventType' => $eventType,
            'url' => $this->urlGenerator->generateUrl('spodsync/subscriber/webhook'),
            'secret' => 'spodsync' // TODO
        ];

        $result = $this->postRequest(self::ACTION_BASE_URL, $params);
        if ($result->getHttpCode() !== 201) {
            throw new \Exception(sprintf("failed to register subscriber: %s / %s", $eventType, $params['url']));
        }
    }

    /**
     * @throws \Exception
     */
    public function unregisterWebhooks()
    {
        $hooksResult = $this->getWebhooks();

        foreach ($hooksResult->getPayload() as $hook) {
            $this->deleteSubscription($hook);
        }
    }

    /**
     * @throws \Exception
     */
    protected function deleteSubscription($hook): void
    {
        $action = sprintf("%s/%s", self::ACTION_BASE_URL, $hook->id);
        $result = $this->sendDeleteRequest($action);

        if ($result->getHttpCode() !== 200) {
            throw new \Exception(sprintf("Failed to delete webhook subscriber #%s", $hook->id));
        }
    }

    /**
     * @return ApiResult
     */
    public function getWebhooks(): ApiResult
    {
        $result = $this->getParsedApiResult(self::ACTION_BASE_URL);
        if ($result->getHttpCode() !== 200) {
            throw new \Exception("failed to get subscribers");
        }

        return $result;
    }
}
