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
use Spod\Sync\Helper\ConfigHelper;
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
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var SpodLoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ResultDecoder $decoder,
        WebhookManager $webhookManager,
        SpodLoggerInterface $logger
    ) {
        $this->configHelper = $configHelper;
        $this->decoder = $decoder;
        $this->logger = $logger;
        $this->webhookManager = $webhookManager;
        return parent::__construct($context);
    }

    public function execute()
    {
        $this->logger->logDebug("webhook: " . $this->getRequest()->getContent());
        if ($this->isSignatureValid()) {
            $this->processValidatedRequest();
        } else {
            throw new \Exception('Invalid signature in webhook request');
        }
    }

    private function isSignatureValid()
    {
        $secret = $this->configHelper->getWebhookSecret();
        $content = $this->getRequest()->getContent();
        $s = hash_hmac('sha256', $content, $secret, true);

        $calculatedHmac = base64_encode($s);
        $this->logger->logDebug("calculated hmac: " . $calculatedHmac);

        $signature = $this->getRequest()->getHeader('X-SPRD-SIGNATURE');
        $this->logger->logDebug("request signature: " . $signature);

        if ($calculatedHmac == $signature) {
            return true;
        } else {
            return false;
        }
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

    private function processValidatedRequest(): void
    {
        $rawJson = $this->getRequest()->getContent();
        $eventType = $this->getEventTypeFromWebhookJson($rawJson);
        $this->webhookManager->saveWebhookEvent($eventType, $rawJson);

        echo "[accepted]";
    }
}
