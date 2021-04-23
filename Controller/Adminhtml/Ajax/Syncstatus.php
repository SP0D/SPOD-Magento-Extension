<?php

namespace Spod\Sync\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Spod\Sync\Helper\StatusHelper;

/**
 * Backend controller which handles the API connect.
 *
 * @package Spod\Sync\Controller\Adminhtml\Ajax
 */
class Syncstatus extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;
    /**
     * @var StatusHelper
     */
    private $statusHelper;

    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        StatusHelper $statusHelper
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->statusHelper = $statusHelper;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Spod_Sync::spodsync');
    }

    public function execute()
    {
        try {
            $data = $this->getSuccessMessage();
        } catch (\Exception $e) {
            $data = $this->getFailedMessage($e->getMessage());
        }

        $result = $this->jsonResultFactory->create();
        $result->setData($data);
        return $result;
    }

    /**
     * Prepare API response for successful connections.
     *
     * @param array $data
     * @return array
     */
    private function getSuccessMessage(): array
    {
        $data = [];
        $data['error'] = 0;
        $data['installDate'] = $this->statusHelper->getInstallDate();
        $data['initsyncStartDate'] = $this->statusHelper->getInitialSyncStartDate();
        $data['initsyncEndDate'] = $this->statusHelper->getInitialSyncEndDate();
        return $data;
    }

    /**
     * Prepare API response when connection failed.
     *
     * @param string $msg
     * @return array
     */
    private function getFailedMessage(string $msg): array
    {
        $data = [];
        $data['error'] = 1;
        $data['message'] = $msg;
        return $data;
    }
}
