<?php

namespace Spod\Sync\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class ConfigHelper extends AbstractHelper
{
    const XML_PATH_APITOKEN = 'spodsync/general/apiToken';
    const XML_PATH_IS_STAGING = 'spodsync/general/is_staging';
    const XML_PATH_LIVEURL = 'spodsync/general/liveurl';
    const XML_PATH_STAGEURL = 'spodsync/general/stagingurl';

    public function getConfigValue($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function isStaging(): bool
    {
        return $this->getConfigValue(self::XML_PATH_IS_STAGING);
    }

    public function getApiUrl(): string
    {
        if ($this->isStaging()) {
            return $this->getConfigValue(self::XML_PATH_STAGEURL);
        } else {
            return $this->getConfigValue(self::XML_PATH_LIVEURL);
        }
    }

    public function getToken(): string
    {
        return $this->getConfigValue(self::XML_PATH_APITOKEN);
    }
}
