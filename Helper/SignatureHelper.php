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
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var SpodLoggerInterface
     */
    private $spodLogger;

    /**
     * SignatureHelper constructor.
     *
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param SpodLoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        SpodLoggerInterface $logger
    ) {
        $this->configHelper = $configHelper;
        $this->spodLogger = $logger;
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
        $secret = $this->configHelper->getWebhookSecret();
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
        $generatedSecret = $this->configHelper->getConfigValue(ConfigHelper::XML_PATH_WEBHOOK_SECRET);
        if (!$generatedSecret) {
            $generatedSecret = $this->generateApiSecret();
            $this->configHelper->saveValue(self::XML_PATH_WEBHOOK_SECRET, $generatedSecret);
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
