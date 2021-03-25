<?php
namespace Spod\Sync\Controller\Subscriber\Webhook\Order;

use Magento\Framework\App\Action\Context;

class Cancelled extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        Context $context)
    {
        return parent::__construct($context);
    }

    public function execute()
    {
        echo "ff";
    }
}
