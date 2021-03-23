<?php
namespace Spod\Sync\Console;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Spod\Sync\Model\WebhookProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebhookQueue extends Command
{
    /** @var WebhookProcessor */
    private $webhookProcessor;

    /** @var State */
    private $state;

    public function __construct(
        WebhookProcessor $webhookProcessor,
        State $state,
        string $name = null
    ) {
        $this->webhookProcessor = $webhookProcessor;
        $this->state = $state;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('spod:webhook:process');
        $this->setDescription('process pending webhooks');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $this->webhookProcessor->processPendingWebhookEvents();
    }


}
