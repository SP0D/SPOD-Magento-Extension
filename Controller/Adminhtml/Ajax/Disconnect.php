<?php

namespace Spod\Sync\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Spod\Sync\Api\SpodLoggerInterface;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\ApiReader\WebhookHandler;
use Spod\Sync\Model\CrudManager\ProductManager;

/**
 * Handle disconnect requests in the backend App.
 *
 * @package Spod\Sync\Controller\Adminhtml\Ajax
 */
class Disconnect extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

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
    /**
     * @var ProductManager
     */
    private $productManager;

    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        StatusHelper $statusHelper,
        SpodLoggerInterface $spodLogger,
        WebhookHandler $webhookHandler,
        ProductManager $productManager
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->productManager = $productManager;
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
        $this->productManager->deleteAllSpodProducts();
        $this->webhookHandler->unregisterWebhooks();
        $this->statusHelper->setApiToken('');
        $this->statusHelper->resetStatusDates();
    }
}
