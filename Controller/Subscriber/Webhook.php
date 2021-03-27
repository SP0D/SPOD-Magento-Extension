<?php
namespace Spod\Sync\Controller\Subscriber;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Model\WebhookFactory;
use Spod\Sync\Model\Mapping\QueueStatus;

class Webhook extends Action  implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /** @var ResultDecoder  */
    private $decoder;
    /** @var WebhookFactory  */
    private $webhookFactory;
    /** @var WebhookEventRepository */
    private $webhookEventRepository;

    public function __construct(
        Context $context,
        ResultDecoder $decoder,
        WebhookFactory $webhookFactory,
        WebhookEventRepository $webhookEventRepository
    ) {
        $this->decoder = $decoder;
        $this->webhookFactory = $webhookFactory;
        $this->webhookEventRepository = $webhookEventRepository;

        return parent::__construct($context);
    }

    public function execute()
    {
        $rawJson = $this->getRequest()->getContent();
        $eventType = $this->getEventTypeFromWebhookJson($rawJson);
        $this->saveWebhookEvent($eventType, $rawJson);

        echo "[accepted]";
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param $eventType
     * @param $rawJson
     * @throws \Exception
     */
    private function saveWebhookEvent($eventType, $rawJson): void
    {
        $webhook = $this->webhookFactory->create();
        $webhook->setEventType($eventType);
        $webhook->setStatus(QueueStatus::STATUS_PENDING);
        $webhook->setPayload($rawJson);

        $this->webhookEventRepository->save($webhook);
    }

    /**
     * @param $rawJson
     * @return mixed
     */
    private function getEventTypeFromWebhookJson($rawJson)
    {
        $responseObject = $this->decoder->parsePayload($rawJson);
        return $responseObject->eventType;
    }
}
