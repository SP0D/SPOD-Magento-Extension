<?php

namespace Spod\Sync\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Spod\Sync\Helper\ConfigHelper;

class Status extends Template
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    public function __construct(
        ConfigHelper $configHelper,
        Context $context,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->configHelper = $configHelper;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    public function getApiToken()
    {
        return $this->configHelper->getToken();
    }
}
