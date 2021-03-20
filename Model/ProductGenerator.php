<?php

namespace Spod\Sync\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;

class ProductGenerator
{
    /** @var ProductFactory */
    private $productFactory;

    /** @var ProductRepository  */
    private $productRepository;

    public function __construct(
        ProductFactory $productFactory,
        ProductRepository $productRepository
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
    }

    public function createAllProducts($apiData)
    {

    }

    public function createProduct($apiData)
    {
        $configurableProduct = $this->createConfigurableProduct($apiData);
        $this->createVariants($apiData);
    }

    private function createConfigurableProduct($apiData)
    {
        $product = $this->productFactory->create();
        $product->setName($apiData->title);
        $product->setDescription($apiData->description);
        $this->productRepository->save($product);

        return $product;
    }

    private function createVariants($apiData)
    {

    }
}
