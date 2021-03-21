<?php

namespace Spod\Sync\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Spod\Sync\Helper\AttributeHelper;
use Spod\Sync\Helper\OptionHelper;

class ProductGenerator
{
    /** @var AttributeHelper */
    private $attributeSetHelper;

    /** @var ImageHandler  */
    private $imageHandler;

    /** @var OptionHelper */
    private $optionHelper;

    /** @var ProductFactory */
    private $productFactory;

    /** @var ProductRepository */
    private $productRepository;

    public function __construct(
        AttributeHelper $attributeSetHelper,
        ImageHandler $imageHandler,
        OptionHelper $optionHelper,
        ProductFactory $productFactory,
        ProductRepository $productRepository
    )
    {
        $this->attributeSetHelper = $attributeSetHelper;
        $this->imageHandler = $imageHandler;
        $this->optionHelper = $optionHelper;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
    }

    public function createAllProducts($apiData)
    {
    }

    public function createProduct($apiData)
    {
        $this->createVariants($apiData);
        //$configurableProduct = $this->createConfigurableProduct($apiData);
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
        $this->createOptionValues($apiData);

        foreach ($apiData->variants as $variantInfo) {
            $this->createVariantProduct($variantInfo, $apiData->images);
        }
    }

    private function createOptionValues($apiData)
    {
        $sizeValues = [];
        $appearanceValues = [];

        foreach ($apiData->variants as $variantInfo) {
            $sizeValues[$variantInfo->sizeName] = $variantInfo->sizeName;
            $appearanceValues[$variantInfo->appearanceName] = $variantInfo->appearanceName;
        }

        $this->optionHelper->addOptionToAttribute('spod_size', $sizeValues);
        $this->optionHelper->addOptionToAttribute('spod_appearance', $appearanceValues);
    }

    private function createVariantProduct($variantInfo, $images)
    {
        $product = $this->getOrCreateSimple($variantInfo->sku);
        $this->assignBaseValues($product, $variantInfo);
        $this->assignSpodValues($product, $variantInfo);
        $this->setStockInfo($product);
        $this->productRepository->save($product);

        $this->imageHandler->downloadAndAssignImages($variantInfo, $images);
    }

    protected function getOrCreateSimple($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
            return $product;
        } catch (\Exception $e) {
            // product not found
            return $this->createNewProduct($sku);
        }
    }

    protected function createNewProduct($sku)
    {
        $product = $this->productFactory->create();
        $product->setSku($sku);

        return $product;
    }

    /**
     * @param Product $product
     * @param $variantInfo
     */
    private function assignBaseValues(Product $product, $variantInfo): void
    {
        $product->setName($variantInfo->productTypeName);
        $product->setStatus(1);
        $product->setVisibility(ProductVisibility::VISIBILITY_NOT_VISIBLE);
        $product->setTypeId(ProductType::TYPE_SIMPLE);
        $product->setPrice($variantInfo->d2cPrice);
        $product->setTaxClassId(0);
        $product->setAttributeSetId($this->attributeSetHelper->getAttrSetId('SPOD'));
    }

    /**
     * @param Product $product
     * @param $variantInfo
     */
    private function assignSpodValues(Product $product, $variantInfo): void
    {
        // configurable
        $product->setSpodSize($this->optionHelper->getDropdownOptionValueForLabel('spod_size', $variantInfo->sizeName));
        $product->setSpodAppearance($this->optionHelper->getDropdownOptionValueForLabel('spod_appearance', $variantInfo->appearanceName));

        // text values
        $product->setData('spod_product_id', sprintf("%s", $variantInfo->productId));
        $product->setData('spod_product_type_id', sprintf("%s", $variantInfo->productTypeId));
        $product->setData('spod_appearance_id', sprintf("%s", $variantInfo->appearanceId));
        $product->setData('spod_size_id', sprintf("%s", $variantInfo->sizeId));
    }

    /**
     * @param Product $product
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setStockInfo(Product $product): void
    {
        $stockData = [
            'use_config_manage_stock' => 0,
            'manage_stock' => 0,
            'is_in_stock' => 1,
        ];

        $product->setStockData($stockData);
        $this->productRepository->save($product);
    }
}
