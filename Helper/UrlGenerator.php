<?php

namespace Spod\Sync\Helper;

use Magento\Store\Model\StoreManagerInterface;

/**
 * Generates URLs, primarily used
 * for the webhook registration process.
 *
 * @package Spod\Sync\Helper
 */
class UrlGenerator
{
    /** @var StoreManagerInterface  */
    private $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Appends a given url path to the base url of
     * the store.
     *
     * @param $urlPath
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generateUrl($urlPath): string
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        if (!preg_match('/.*\//', $baseUrl)) {
            $baseUrl .= '/';
        }

        return sprintf('%s%s', $baseUrl, $urlPath);
    }
}
