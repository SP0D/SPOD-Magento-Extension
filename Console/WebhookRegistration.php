<?php
namespace Spod\Sync\Console;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Spod\Sync\Model\ApiReader\WebhookHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command WebhookRegistration
 *
 * Can be used on the command line
 * to register, unregister and list
 * all webhooks.
 *
 * @package Spod\Sync\Console
 */
class WebhookRegistration extends Command
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
        $this->state = $state;
        $this->webhookHandler = $webhookHandler;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('spod:webhook:registration');
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        if ($input->getOption('register')) {
            $output->write("<info>Registering webhooks...</info>");
            $this->webhookHandler->registerWebhooks();
            $output->writeln("<info>done</info>");

        } elseif ($input->getOption('list')) {
            $this->listWebhooks($output);

        } elseif ($input->getOption('unregister')) {
            $this->webhookHandler->unregisterWebhooks();
        }
    }

    /**
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function listWebhooks(OutputInterface $output)
    {
        $hooksResult = $this->webhookHandler->getWebhooks();

        foreach ($hooksResult->getPayload() as $hook) {
            $output->writeln(sprintf("<info>%s: %s</info>", $hook->eventType, $hook->url));
        }

        return 0;
    }
}
