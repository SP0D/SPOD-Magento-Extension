<?php

namespace Spod\Sync\Helper;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

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

    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        parent::__construct($context);
    }

    public function clearConfigCache()
    {
        $this->cacheTypeList->cleanType(self::CACHE_TYPECODE_CONFIG);
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }
}
