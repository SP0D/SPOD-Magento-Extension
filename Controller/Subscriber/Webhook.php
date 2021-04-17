<?php
namespace Spod\Sync\Controller\Subscriber;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\SignatureHelper;
use Spod\Sync\Model\CrudManager\WebhookManager;
use Spod\Sync\Model\WebhookFactory;

class Webhook extends Action  implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /** @var ResultDecoder  */
    private $decoder;
    /**
     * @var WebhookManager
     */
    private $webhookManager;
    /**
     * @var SpodLoggerInterface
     */
    private $logger;
    /**
     * @var SignatureHelper
     */
    private $signatureHelper;

    public function __construct(
        Context $context,
        ResultDecoder $decoder,
        WebhookManager $webhookManager,
        SignatureHelper $signatureHelper,
        SpodLoggerInterface $logger
    ) {
        $this->decoder = $decoder;
        $this->logger = $logger;
        $this->webhookManager = $webhookManager;
        $this->signatureHelper = $signatureHelper;
        return parent::__construct($context);
    }

    public function execute()
    {
        $this->logger->logDebug($this->getRequest()->getContent(), 'webhook received');
        $signature = $this->getRequest()->getHeader('X-SPRD-SIGNATURE');
        $requestBody = $this->getRequest()->getContent();

        if ($this->signatureHelper->isSignatureValid($requestBody, $signature)) {
            $this->saveValidatedRequest();
            echo "[accepted]";
        } else {
            throw new \Exception('Invalid signature in webhook request');
        }
    }

    /**
     * Save webhook event to db.
     *
     * @throws \Exception
     */
    private function saveValidatedRequest(): void
    {
        $rawJson = $this->getRequest()->getContent();
        $eventType = $this->getEventTypeFromWebhookJson($rawJson);
        $this->webhookManager->saveWebhookEvent($eventType, $rawJson);
    }

    /**
     * Reads the webhook event name.
     * (like Article.added, Article.removed...)
     *
     * @param $rawJson
     * @return mixed
     */
    private function getEventTypeFromWebhookJson($rawJson)
    {
        $responseObject = $this->decoder->parsePayload($rawJson);
        return $responseObject->eventType;
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
}
