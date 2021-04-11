<?php

namespace Spod\Sync\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Spod\Sync\Model\ApiReader\AuthenticationHandler;

class Syncstatus extends Action
{
    /**
     * @var AuthenticationHandler
     */
    private $authHandler;
    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    public function __construct(
        AuthenticationHandler $authHandler,
        Context $context,
        JsonFactory $jsonResultFactory
    ) {
        parent::__construct($context);
        $this->authHandler = $authHandler;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Spod_Sync::spodsync');
    }

    public function execute()
    {
        $apiToken = $this->getRequest()->getParam('apiToken');
        $data = [];

        if ($this->authHandler->isTokenValid($apiToken)) {
            $data['error'] = 0;
            $data['message'] = 'API key is valid';

            $this->handleValidKey($apiToken);

        } else {
            $data['error'] = 1;
            $data['message'] = 'Invalid API key';
            $data['syncstatus'] = 0;
        }

        $result = $this->jsonResultFactory->create();
        $result->setData($data);

        return $result;
    }

    private function handleValidKey(string $apiToken)
    {

    }
}
