<?php
namespace Spod\Sync\Controller\Subscriber;

use Magento\Framework\App\Action\Context;

class Webhook extends \Magento\Framework\App\Action\Action
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
