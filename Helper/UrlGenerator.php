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
        return sprintf('%s/%s', $this->storeManager->getStore()->getBaseUrl(), $urlPath);
    }
}
