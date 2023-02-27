<?php

namespace Spod\Sync\Controller\Subscriber;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\SignatureHelper;
use Spod\Sync\Model\CrudManager\WebhookManager;

/**
 * Controller which handles the subscriptions,
 * i.e. receives all webhook requests.
 *
 * @package Spod\Sync\Controller\Subscriber
 */
class Webhook extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
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

    /** @var JsonFactory */
    private $jsonResultFactory;

    public function __construct(
        Context $context,
        WebhookManager $webhookManager,
        SignatureHelper $signatureHelper,
        SpodLoggerInterface $logger,
        JsonFactory $jsonResultFactory
    ) {
        $this->logger = $logger;
        $this->webhookManager = $webhookManager;
        $this->signatureHelper = $signatureHelper;
        $this->jsonResultFactory = $jsonResultFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $this->logger->logDebug($this->getRequest()->getContent(), 'webhook received');
        $signature = $this->getRequest()->getHeader('X-SPRD-SIGNATURE');
        $requestBody = $this->getRequest()->getContent();

        if ($this->signatureHelper->isSignatureValid($requestBody, $signature)) {
            $this->saveValidatedRequest();
            $result = $this->jsonResultFactory->create();
            $result->setHttpResponseCode(202);
            $result->setData('[accepted]');
            return $result;
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
        $responseObject = json_decode($rawJson, false);
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
