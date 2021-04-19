<?php

namespace Spod\Sync\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\CacheHelper;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\ApiReader\WebhookHandler;

/**
 * Handle disconnect requests in the backend App.
 *
 * @package Spod\Sync\Controller\Adminhtml\Ajax
 */
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
    /**
     * @var SpodLoggerInterface
     */
    private $spodLogger;

    public function __construct(
        CacheHelper $cacheHelper,
        ConfigHelper $configHelper,
        Context $context,
        JsonFactory $jsonResultFactory,
        StatusHelper $statusHelper,
        SpodLoggerInterface $spodLogger,
        WebhookHandler $webhookHandler
    ) {
        parent::__construct($context);
        $this->cacheHelper = $cacheHelper;
        $this->configHelper = $configHelper;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->statusHelper = $statusHelper;
        $this->spodLogger = $spodLogger;
        $this->webhookHandler = $webhookHandler;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Spod_Sync::spodsync');
    }

    public function execute()
    {
        $data = [];

        try {
            $this->handleDisconnect();
            $data['error'] = 0;
            $data['message'] = 'disconnected';
        } catch (\Exception $e) {
            $data['error'] = 1;
            $data['message'] = $e->getMessage();
            $this->spodLogger->logError(
                'API disconnect',
                $e->getMessage(),
                $e->getTraceAsString()
            );
        }

        $result = $this->jsonResultFactory->create();
        $result->setData($data);

        return $result;
    }

    /**
     * Disconnect from API
     * @throws \Exception
     */
    private function handleDisconnect()
    {
        $this->webhookHandler->unregisterWebhooks();
        $this->statusHelper->setApiToken('');
        $this->statusHelper->resetStatusDates();
        $this->cacheHelper->clearConfigCache();
    }
}
