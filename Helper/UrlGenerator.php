<?php

namespace Spod\Sync\Helper;

use Magento\Store\Model\StoreManagerInterface;

class UrlGenerator
{
    /** @var StoreManagerInterface  */
    private $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    public function generateUrl($urlPath): string
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        if (!preg_match('/.*\//', $baseUrl)) {
            $baseUrl .= '/';
        }

        return sprintf('%s%s', $baseUrl, $urlPath);
    }
}
