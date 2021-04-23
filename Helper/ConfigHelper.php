<?php

namespace Spod\Sync\Helper;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * Used to access the system config for
 * reading and writing values.
 *
 * @package Spod\Sync\Helper
 */
class ConfigHelper extends AbstractHelper
{
    const XML_PATH_IS_STAGING = 'spodsync/general/is_staging';
    const XML_PATH_DEBUG_LOGGING = 'spodsync/general/debug_logging';
    const XML_PATH_LIVEURL = 'spodsync/general/liveurl';
    const XML_PATH_STAGEURL = 'spodsync/general/stagingurl';
    const XML_PATH_FROM_FIRSTNAME = 'spodsync/shipping/from_firstname';
    const XML_PATH_FROM_LASTNAME = 'spodsync/shipping/from_lastname';
    const XML_PATH_SHIPPING_PREMIUM = 'spodsync/shipping/premium_shipping';
    const XML_PATH_SHIPPING_EXPRESS = 'spodsync/shipping/express_shipping';
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var StatusHelper
     */
    private $statusHelper;

    public function __construct(
        Context $context,
        StatusHelper $statusHelper,
        WriterInterface $configWriter
    ) {
        $this->configWriter = $configWriter;
        $this->statusHelper = $statusHelper;
        parent::__construct($context);
    }

    /**
     * Method for reading Magento system.xml config values
     * for a specific config path.
     *
     * @param $path
     * @param null $storeId
     * @return mixed
     */
    public function getConfigValue($path, $storeId = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Return wether to activate debug logging or not.
     *
     * @return bool
     */
    public function debugLogging(): bool
    {
        return $this->getConfigValue(self::XML_PATH_DEBUG_LOGGING);
    }

    /**
     * Return wether to use the staging environment or not.
     *
     * @return bool
     */
    public function isStaging(): bool
    {
        return $this->getConfigValue(self::XML_PATH_IS_STAGING);
    }

    /**
     * Get the API URL.
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        if ($this->isStaging()) {
            return $this->getConfigValue(self::XML_PATH_STAGEURL);
        } else {
            return $this->getConfigValue(self::XML_PATH_LIVEURL);
        }
    }

    /**
     * Get method codes for premium shipping methods.
     *
     * @return false|string[]
     */
    public function getPremiumShippingMapping()
    {
        $mapping = $this->getConfigValue(self::XML_PATH_SHIPPING_PREMIUM);
        return explode(',', $mapping);
    }

    /**
     * Get method codes for express shipping methods.
     *
     * @return false|string[]
     */
    public function getExpressShippingMapping()
    {
        $mapping = $this->getConfigValue(self::XML_PATH_SHIPPING_EXPRESS);
        return explode(',', $mapping);
    }

    /**
     * Get API Token.
     *
     * @return string
     */
    public function getToken(): ?string
    {
        return $this->statusHelper->getApiToken();
    }

    /**
     * Get lastname for FromAddress
     *
     * @return string|null
     */
    public function getFromLastname(): ?string
    {
        $lastname = $this->getConfigValue(self::XML_PATH_FROM_FIRSTNAME);
        if ($lastname) {
            return $lastname;
        } else {
            throw new \Exception("FromAddress lastname was not set in system config but is required.");
        }
    }

    /**
     * Get firstname for FromAddress
     *
     * @return string|null
     */
    public function getFromFirstname(): string
    {
        if ($firstname = $this->getConfigValue(self::XML_PATH_FROM_FIRSTNAME)) {
            return $firstname;
        } else {
            return '';
        }
    }

    /**
     * Save a config value using the given config path.
     *
     * @param $path
     * @param $value
     */
    public function saveValue($path, $value)
    {
        $this->configWriter->save($path, $value);
    }
}
