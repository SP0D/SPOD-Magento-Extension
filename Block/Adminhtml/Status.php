<?php

namespace Spod\Sync\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Spod\Sync\Helper\StatusHelper;

/**
 * Status Block for the Backend-Installer.
 *
 * @package Spod\Sync\Block\Adminhtml
 */
class Status extends Template
{
    /**
     * @var StatusHelper
     */
    private $statusHelper;

    public function __construct(
        Context $context,
        StatusHelper $statusHelper,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->statusHelper = $statusHelper;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * Get API token from config and return it.
     *
     * @return string
     */
    public function getApiToken()
    {
        return $this->statusHelper->getApiToken();
    }
}
