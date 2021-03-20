<?php
namespace Spod\Sync\Controller\Subscriber\Order;

class Cancelled extends \Magento\Framework\App\Action\Action
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
