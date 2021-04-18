<?php

namespace Spod\Sync\Helper;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Responsible for cleaning certain caches.
 *
 * @package Spod\Sync\Helper
 */
class CacheHelper extends AbstractHelper
{
    const CACHE_TYPECODE_CONFIG = 'config';
    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;
    /**
     * @var Pool
     */
    private $cacheFrontendPool;

    /**
     * CacheHelper constructor.
     * @param Context $context
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     */
    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool
    ) {
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->cacheTypeList = $cacheTypeList;
        parent::__construct($context);
    }

    /**
     * Clears the config cache
     */
    public function clearConfigCache()
    {
        $this->cacheTypeList->cleanType(self::CACHE_TYPECODE_CONFIG);
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }
}
