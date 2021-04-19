<?php

namespace Spod\Sync\Model\CrudManager;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;

use Magento\Store\Api\StoreManagementInterface;
use Spod\Sync\Api\ResultDecoder;
use Spod\Sync\Helper\AttributeHelper;
use Spod\Sync\Helper\ImageHelper;
use Spod\Sync\Helper\OptionHelper;
use Spod\Sync\Model\ApiResult;

/**
 * Creates, updates and removes products locally
 * in the Magento 2 database.
 *
 * @package Spod\Sync\Model\CrudManager
 */
class ProductManager
{
    /** @var AttributeHelper */
    private $attributeHelper;

    /** @var ResultDecoder  */
    private $decoder;

    /** @var ImageHelper  */
    private $imageHelper;

    /** @var OptionHelper */
    private $optionHelper;

    /** @var Factory */
    private $optionsFactory;

    /** @var ProductFactory */
    private $productFactory;

    /** @var ProductRepository */
    private $productRepository;

    /** @var SearchCriteriaBuilder  */
    private $searchCriteriaBuilder;

    public function __construct(
        AttributeHelper $attributeSetHelper,
        Factory $factory,
        ImageHelper $imageHelper,
        OptionHelper $optionHelper,
        ProductFactory $productFactory,
        ProductRepository $productRepository,
        ResultDecoder $decoder,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeHelper = $attributeSetHelper;
        $this->decoder = $decoder;
        $this->optionsFactory = $factory;
        $this->imageHelper = $imageHelper;
        $this->optionHelper = $optionHelper;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Create all fetched products, one product incl.
     * it's variants at a time.
     *
     * @param ApiResult $apiResult
     */
    public function createAllProducts(ApiResult $apiResult)
    {
        $apiData = $this->decoder->parsePayload($apiResult->getPayload());

        foreach ($apiData->items as $articleData) {
            $this->createOptionValues($articleData);
            $this->saveProduct($articleData);
        }
    }

    /**
     * Create one product incl. it's variants
     *
     * @param ApiResult $apiResult
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function createProduct(ApiResult $apiResult)
    {
        $apiData = $this->decoder->parsePayload($apiResult->getPayload());
        $this->saveProduct($apiData);
    }

    /**
     * @param $apiData
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function saveProduct($apiData): void
    {
        $configurableProduct = $this->prepareConfigurableProduct($apiData);
        $this->productRepository->save($configurableProduct);

        $variants = $this->createVariants($apiData);
        $configurableProduct = $this->productRepository->get($configurableProduct->getSku());
        $configurableProduct = $this->assignVariants($configurableProduct, $variants);
        $configurableProduct = $this->setStockInfo($configurableProduct);
        $this->imageHelper->assignConfigurableImages($configurableProduct, $apiData->images);
        $this->productRepository->save($configurableProduct);
    }

    public function updateProduct(ApiResult $apiResult)
    {
        $apiData = $this->decoder->parsePayload($apiResult->getPayload());

        $configurable = $this->getProductBySpodId($apiData->id);
        if (!$configurable) {
            throw new \Exception(sprintf("Product with ID %s could not be found", $apiData->id));
        }

        $configurable->setName($apiData->title);
        $this->productRepository->save($configurable);

        // unassign and remove current variants
        $variantProducts = $this->getVariantProducts($configurable);
        $configurable->setAssociatedProductIds([]);
        $this->deleteVariantsOfConfigurable($variantProducts);
        $this->productRepository->save($configurable);

        // recreate variants
        $configurable = $this->productRepository->get($configurable->getSku());
        $variants = $this->createVariants($apiData);
        $this->assignVariants($configurable, $variants);
        $this->productRepository->save($configurable);

        // configurable images
        $this->imageHelper->resetOldImages($configurable);
        $this->imageHelper->assignConfigurableImages($configurable, $apiData->images);
        $this->productRepository->save($configurable);
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
        $product->setName(sprintf("%s - %d", $apiData->title, $apiData->id));
        $product->setTypeId(Configurable::TYPE_CODE);
        $product->setDescription($apiData->description);
        $product->setVisibility(ProductVisibility::VISIBILITY_BOTH);
        $product->setSpodProduct(true);
        $product->setSpodProductId($apiData->id);

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
        $variants = [];
        foreach ($apiData->variants as $variantInfo) {
            $variant = $this->createVariantProduct($variantInfo);
            $this->imageHelper->resetOldImages($variant);
            $this->productRepository->save($variant);

            // required to get full product, otherwise image assignment fails
            $savedProduct = $this->productRepository->get($variant->getSku());
            $this->imageHelper->downloadAndAssignImages($savedProduct, $variantInfo->imageIds, $apiData->images);
            $this->productRepository->save($savedProduct);

            $variants[] = $variant;
        }

        return $variants;
    }

    /**
     * @param $apiData
     */
    public function createOptionValues($apiData)
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

    private function createVariantProduct($variantInfo)
    {
        $product = $this->getOrCreateSimple($variantInfo->sku);
        $this->assignBaseValues($product, $variantInfo);
        $this->assignSpodValues($product, $variantInfo);
        $this->setStockInfo($product);

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

    /**
     * @param $spodProductId
     * @throws \Magento\Framework\Exception\StateException
     */
    public function deleteProductAndVariants($spodProductId)
    {
        $configurable = $this->getProductBySpodId($spodProductId);
        if ($configurable) {
            $variantProducts = $this->getVariantProducts($configurable);
            $this->productRepository->delete($configurable);
            $this->deleteVariantsOfConfigurable($variantProducts);
        }
    }

    /**
     * @param $spodProductId
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function getProductBySpodId($spodProductId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('spod_product_id', $spodProductId, 'eq')->create();
        $searchResults = $this->productRepository->getList($searchCriteria);
        $products = $searchResults->getItems();

        foreach ($products as $product) {
            return $product;
        }

        return false;
    }

    /**
     * @param $variantProducts
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function deleteVariantsOfConfigurable($variantProducts): void
    {
        foreach ($variantProducts as $variant) {
            $this->productRepository->delete($variant);
        }
    }

    /**
     * @param Product $configurable
     * @return mixed
     */
    protected function getVariantProducts(Product $configurable)
    {
        $variantProducts = $configurable
            ->getTypeInstance()
            ->getUsedProducts($configurable);
        return $variantProducts;
    }
}
