<?php
namespace Spod\Sync\Controller\Subscriber;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Model\WebhookFactory;
use Spod\Sync\Model\QueueStatus;

class Webhook extends Action  implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private $decoder;
    private $webhookFactory;
    private $webhookRepository;

    public function __construct(
        Context $context,
        ResultDecoder $decoder,
        WebhookFactory $webhookFactory
    ) {
        $this->decoder = $decoder;
        $this->webhookFactory = $webhookFactory;

        return parent::__construct($context);
    }

    public function execute()
    {
        $rawJson = $this->getRequest()->getContent();
        $responseObject = $this->decoder->parsePayload($rawJson);

        $webhook = $this->webhookFactory->create();
        $webhook->setEventType($responseObject->eventType);
        $webhook->setStatus(QueueStatus::STATUS_PENDING);
        $webhook->setPayload($rawJson);
        $webhook->save($webhook);

        echo "[accepted]";
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
