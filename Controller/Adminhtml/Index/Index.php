<?php

namespace Spod\Sync\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Backend Controller for the installer and status application.
 *
 * @package Spod\Sync\Controller\Adminhtml\Index
 */
class Index extends Action
{
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Spod_Sync::spodsync');
    }

    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}
