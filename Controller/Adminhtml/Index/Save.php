<?php

namespace Spod\Sync\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\View\Result\PageFactory;
use Spod\Sync\Helper\ConfigHelper;

class Save extends Action
{
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        WriterInterface $configWriter
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->resultPageFactory = $resultPageFactory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Spod_Sync::spodsync');
    }

    public function execute()
    {
        $this->configWriter->save(ConfigHelper::XML_PATH_APITOKEN, $apiToken);

        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}
