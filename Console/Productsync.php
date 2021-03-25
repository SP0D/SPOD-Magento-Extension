<?php
namespace Spod\Sync\Console;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Spod\Sync\Model\ApiReader\ArticleHandler;
use Spod\Sync\Model\CrudManager\ProductManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Productsync extends Command
{
    /** @var ProductManager */
    private $productGenerator;
    /** @var ArticleHandler */
    private $articleHandler;
    /** @var State  */
    private $state;

    public function __construct(
        ArticleHandler $articleHandler,
        ProductManager $productGenerator,
        State $state,
        string $name = null
    ) {
        $this->articleHandler = $articleHandler;
        $this->productGenerator = $productGenerator;
        $this->state = $state;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('spod:productsync');
        $this->setDescription('Synchronize SPOD products');
        $this->setDefinition([
            new InputOption(
                'article-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Article ID'
            )
        ]);

        parent::configure();
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        if ($id = $input->getOption('article-id')) {
            $apiResult = $this->articleHandler->getArticleById($id);
            $this->productGenerator->createProduct($apiResult);
        } else {
            $apiResult = $this->articleHandler->getAllArticles();
            $this->productGenerator->createAllProducts($apiResult);
        }
    }
}
