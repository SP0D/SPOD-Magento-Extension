<?php

namespace Spod\Sync\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Spod\Sync\Helper\CacheHelper;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\ApiReader\WebhookHandler;

class Disconnect extends Action
{
    /**
     * @var CacheHelper
     */
    private $cacheHelper;
    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var StatusHelper
     */
    private $statusHelper;
    /**
     * @var WebhookHandler
     */
    private $webhookHandler;

    public function __construct(
        CacheHelper $cacheHelper,
        ConfigHelper $configHelper,
        Context $context,
        JsonFactory $jsonResultFactory,
        StatusHelper $statusHelper,
        WebhookHandler $webhookHandler
    ) {
        parent::__construct($context);
        $this->cacheHelper = $cacheHelper;
        $this->configHelper = $configHelper;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->statusHelper = $statusHelper;
        $this->webhookHandler = $webhookHandler;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Spod_Sync::spodsync');
    }

    public function execute()
    {
        $this->handleDisconnect();

        $data = [];
        $data['error'] = 0;
        $data['message'] = 'disconnected';

        $result = $this->jsonResultFactory->create();
        $result->setData($data);

        return $result;
    }

    private function handleDisconnect()
    {
        $this->configHelper->saveApiToken('');
        $this->statusHelper->resetStatusDates();
        $this->cacheHelper->clearConfigCache();
        $this->webhookHandler->unregisterWebhooks();
    }
}
