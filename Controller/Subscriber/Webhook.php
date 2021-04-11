<?php
namespace Spod\Sync\Controller\Subscriber;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Model\CrudManager\WebhookManager;
use Spod\Sync\Model\Repository\WebhookEventRepository;
use Spod\Sync\Model\WebhookFactory;
use Spod\Sync\Model\Mapping\QueueStatus;

class Webhook extends Action  implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /** @var ResultDecoder  */
    private $decoder;
    /**
     * @var WebhookManager
     */
    private $webhookManager;

    public function __construct(
        Context $context,
        ResultDecoder $decoder,
        WebhookManager $webhookManager
    ) {
        $this->decoder = $decoder;
        $this->webhookManager = $webhookManager;
        return parent::__construct($context);
    }

    public function execute()
    {
        $rawJson = $this->getRequest()->getContent();
        $eventType = $this->getEventTypeFromWebhookJson($rawJson);
        $this->webhookManager->saveWebhookEvent($eventType, $rawJson);

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
     * @param $rawJson
     * @return mixed
     */
    private function getEventTypeFromWebhookJson($rawJson)
    {
        $responseObject = $this->decoder->parsePayload($rawJson);
        return $responseObject->eventType;
    }
}
