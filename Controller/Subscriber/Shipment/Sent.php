<?php
namespace Spod\Sync\Controller\Subscriber\Shipment;

class Sent extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context)
    {
        return parent::__construct($context);
    }

    public function execute()
    {
        echo "ff";
    }
}
