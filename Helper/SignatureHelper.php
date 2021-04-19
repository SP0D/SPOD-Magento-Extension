<?php

namespace Spod\Sync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Spod\Sync\Api\SpodLoggerInterface;

/**
 * Used for the webhook secret creation and validation.
 *
 * @package Spod\Sync\Helper
 */
class SignatureHelper extends AbstractHelper
{
    /**
     * @var SpodLoggerInterface
     */
    private $spodLogger;
    /**
     * @var StatusHelper
     */
    private $statusHelper;

    /**
     * SignatureHelper constructor.
     * @param Context $context
     * @param SpodLoggerInterface $logger
     * @param StatusHelper $statusHelper
     */
    public function __construct(
        Context $context,
        SpodLoggerInterface $logger,
        StatusHelper $statusHelper
    ) {
        $this->spodLogger = $logger;
        $this->statusHelper = $statusHelper;
        parent::__construct($context);
    }

    /**
     * Checks wether the received body of a received
     * webhook call is valid. It uses the shared secret
     * that was generated and stored when the subscriber url
     * was registered.
     *
     * @param $content
     * @param $signature
     * @return bool
     */
    public function isSignatureValid($content, $signature): bool
    {
        $secret = $this->statusHelper->getWebhookSecret();
        $hmacBinary = hash_hmac('sha256', $content, $secret, true);

        $calculatedHmac = base64_encode($hmacBinary);
        $this->spodLogger->logDebug("calculated hmac: " . $calculatedHmac);
        $this->spodLogger->logDebug("request signature: " . $signature);

        if ($calculatedHmac == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Try to get the webhook secret and, if called for the
     * first time, generate one before returning it.
     *
     * @return string
     */
    public function getWebhookSecret(): string
    {
        $generatedSecret = $this->statusHelper->getWebhookSecret();
        if (!$generatedSecret) {
            $generatedSecret = $this->generateApiSecret();
            $this->statusHelper->setWebhookSecret($generatedSecret);
        }

        return $generatedSecret;
    }

    /**
     * Generate a webhook secret, which is registered
     * with each webhook subscription and used later
     * to validate incoming webhook requests.
     *
     * @return string
     */
    private function generateApiSecret(): string
    {
        return uniqid();
    }
}
