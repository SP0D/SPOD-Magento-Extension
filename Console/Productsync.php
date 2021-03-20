<?php
namespace Spod\Sync\Console;

use Spod\Sync\Model\ApiReader\ArticleHandler;
use Spod\Sync\Model\ProductGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Productsync extends Command
{
    /** @var ProductGenerator */
    private $productGenerator;
    /** @var ArticleHandler */
    private $articleHandler;

    public function __construct(
        ArticleHandler $articleHandler,
        ProductGenerator $productGenerator,
        string $name = null
    ) {
        $this->articleHandler = $articleHandler;
        $this->productGenerator = $productGenerator;
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
        if ($id = $input->getOption('article-id')) {
            $product = $this->articleHandler->getArticleById($id);
            $this->productGenerator->createProduct($product);
        } else {
            $products = $this->articleHandler->getAllArticles();
            $this->productGenerator->createAllProducts($products);
        }
    }
}
