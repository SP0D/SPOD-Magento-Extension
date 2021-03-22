<?php

namespace Spod\Sync\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

use Magento\Framework\Exception\NoSuchEntityException;

use Spod\Sync\Helper\AttributeHelper;
use Spod\Sync\Helper\OptionHelper;

class ProductGenerator
{
    /** @var AttributeHelper */
    private $attributeHelper;

    /** @var ImageHandler  */
    private $imageHandler;

    /** @var OptionHelper */
    private $optionHelper;

    /** @var Factory */
    private $optionsFactory;

    /** @var ProductFactory */
    private $productFactory;

    /** @var ProductRepository */
    private $productRepository;

    public function __construct(
        AttributeHelper $attributeSetHelper,
        Factory $factory,
        ImageHandler $imageHandler,
        OptionHelper $optionHelper,
        ProductFactory $productFactory,
        ProductRepository $productRepository
    ) {
        $this->attributeHelper = $attributeSetHelper;
        $this->optionsFactory = $factory;
        $this->imageHandler = $imageHandler;
        $this->optionHelper = $optionHelper;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
    }

    /**
     * Create all fetched products, one product incl.
     * it's variants at a time.
     *
     * @param $apiData
     */
    public function createAllProducts($apiData)
    {
    }

    /**
     * Create one product incl. it's variants
     *
     * @param $apiData
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function createProduct($apiData)
    {
        $configurableProduct = $this->prepareConfigurableProduct($apiData);
        $this->productRepository->save($configurableProduct);

        $variants = $this->createVariants($apiData);

        $configurableProduct = $this->productRepository->get($configurableProduct->getSku());
        $configurableProduct = $this->assignVariants($configurableProduct, $variants);
        $configurableProduct = $this->setStockInfo($configurableProduct);

        $this->imageHandler->assignConfigurableImages($configurableProduct, $apiData->images);
        $this->productRepository->save($configurableProduct);
    }

    /**
     * @param $apiData
     * @return \Magento\Catalog\Api\Data\ProductInterface|Product|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareConfigurableProduct($apiData)
    {
        $sku = sprintf('sp%s', $apiData->id);
        $product = $this->getOrCreateSimple($sku);
        $product->setName($apiData->title);
        $product->setTypeId(Configurable::TYPE_CODE);
        $product->setDescription($apiData->description);
        $product->setVisibility(ProductVisibility::VISIBILITY_BOTH);
        $product->setSpodProduct(true);

        $attrSetId = $this->attributeHelper->getAttrSetId('SPOD');
        $product->setAttributeSetId($attrSetId);

        return $product;
    }

    /**
     * @param Product $confProduct
     * @param array $variants
     * @return Product
     */
    private function assignVariants(Product $confProduct, array $variants)
    {
        $appearanceAttr = $this->attributeHelper->getAttributeByCode('spod_appearance');
        $sizeAttr = $this->attributeHelper->getAttributeByCode('spod_size');

        // prepare conf. attributes
        $confProduct->getTypeInstance()->setUsedProductAttributeIds([$appearanceAttr->getId(), $sizeAttr->getId()], $confProduct);
        $configurableAttributesData = $confProduct->getTypeInstance()->getConfigurableAttributesAsArray($confProduct);
        $confProduct->setConfigurableAttributesData($configurableAttributesData);

        // assign values
        $configurableAttributesData[$sizeAttr->getId()]['values'] = $this->attributeHelper->getPreparedOptionValues($sizeAttr);
        $configurableAttributesData[$appearanceAttr->getId()]['values'] = $this->attributeHelper->getPreparedOptionValues($appearanceAttr);
        $confProduct->setAssociatedProductIds($this->getAssociatedProductIds($variants));
        $confProduct->setCanSaveConfigurableAttributes(true);

        // set extension attributes
        $configurableOptions = $this->optionsFactory->create($configurableAttributesData);
        $extensionConfigurableAttributes = $confProduct->getExtensionAttributes();
        $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionConfigurableAttributes->setConfigurableProductLinks($this->getAssociatedProductIds($variants));
        $confProduct->setExtensionAttributes($extensionConfigurableAttributes);

        return $confProduct;
    }

    /**
     * @param $apiData
     * @return array
     */
    private function createVariants($apiData)
    {
        $this->createOptionValues($apiData);

        $variants = [];
        foreach ($apiData->variants as $variantInfo) {
            $variant = $this->createVariantProduct($variantInfo, $apiData->images);
            $variants[] = $variant;
        }

        return $variants;
    }

    /**
     * @param $apiData
     */
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

        return $product;
    }

    protected function getOrCreateSimple($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
            return $product;
        } catch (NoSuchEntityException $e) {
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
        $product->setAttributeSetId($this->attributeHelper->getAttrSetId('SPOD'));
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
        $product->setData('spod_product', true);
    }

    /**
     * @param Product $product
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setStockInfo(Product $product): Product
    {
        $stockData = [
            'use_config_manage_stock' => 0,
            'manage_stock' => 0,
            'is_in_stock' => 1,
        ];

        $product->setStockData($stockData);

        return $product;
    }

    /**
     * @param array $variants
     * @return array
     */
    private function getAssociatedProductIds(array $variants): array
    {
        $associatedProductIds = [];

        foreach ($variants as $variant) {
            $fullVariant = $this->productRepository->get($variant->getSku());
            if (!in_array($fullVariant->getId(), $associatedProductIds)) {
                $associatedProductIds[] = $fullVariant->getId();
            }
        }

        return $associatedProductIds;
    }

}
