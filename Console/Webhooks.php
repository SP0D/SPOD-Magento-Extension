<?php
namespace Spod\Sync\Console;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Spod\Sync\Model\ApiReader\ArticleHandler;
use Spod\Sync\Model\ApiReader\WebhookHandler;
use Spod\Sync\Model\ProductGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Webhooks extends Command
{
    /** @var WebhookHandler */
    private $webhookHandler;
    /** @var State  */
    private $state;

    public function __construct(
        WebhookHandler $webhookHandler,
        State $state,
        string $name = null
    ) {
        $this->webhookHandler = $webhookHandler;
        $this->state = $state;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('spod:webhook');
        $this->setDescription('Register/unregister webhooks');
        $this->setDefinition([
            new InputOption(
                'register',
                null,
                InputOption::VALUE_NONE,
                'Register all webhooks'
            ),
            new InputOption(
                'list',
                null,
                InputOption::VALUE_NONE,
                'List all webhooks'
            ),
            new InputOption(
                'unregister',
                null,
                InputOption::VALUE_NONE,
                'Unregister all webhooks'
            )
        ]);

        parent::configure();
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        if ($input->getOption('register')) {
            echo "Registering webhooks...";
            $this->webhookHandler->registerWebhooks();
            echo "done\n";

        } elseif ($input->getOption('list')) {
            $this->listWebhooks();

        } elseif ($input->getOption('unregister')) {
            $this->webhookHandler->unregisterWebhooks();
        }
    }

    protected function listWebhooks(): void
    {
        $hooksResult = $this->webhookHandler->getWebhooks();
        foreach ($hooksResult->getPayload() as $hook) {
            echo sprintf("- %s: %s\n", $hook->eventType, $hook->url);
        }
    }
}
