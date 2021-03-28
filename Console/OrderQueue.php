<?php
namespace Spod\Sync\Console;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Spod\Sync\Model\QueueProcessor\OrderProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderQueue extends Command
{
    /** @var OrderProcessor */
    private $orderProcessor;

    /** @var State */
    private $state;

    public function __construct(
        OrderProcessor $orderProcessor,
        State $state,
        string $name = null
    ) {
        $this->orderProcessor = $orderProcessor;
        $this->state = $state;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('spod:order:queue');
        $this->setDescription('process pending orders');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $this->orderProcessor->processPendingNewOrders();
    }


}
