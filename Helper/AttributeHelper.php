<?php

declare(strict_types=1);

namespace Spod\Sync\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Eav\Model\AttributeSetRepository;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Helper which provides methods for managing
 * required attributes.
 *
 * @package Spod\Sync\Helper
 */
class AttributeHelper extends AbstractHelper
{
    /** @var CollectionFactory */
    private $attributeSetCollection;

    /** @var AttributeSetFactory */
    private $attributeSetFactory;

    /** @var AttributeSetRepository */
    private $attributeSetRepository;

    /** @var CategorySetupFactory */
    private $categorySetupFactory;

    /** @var Config */
    private $eavConfig;

    public function __construct(
        AttributeSetFactory $attributeSetFactory,
        AttributeSetRepository $attributeSetRepository,
        CategorySetupFactory $categorySetupFactory,
        CollectionFactory $attributeSetCollection,
        Config $eavConfig,
        Context $context
    ) {
        $this->attributeSetCollection = $attributeSetCollection;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->eavConfig = $eavConfig;

        return parent::__construct($context);
    }

    public function getAttributeByCode(string $attrCode): AbstractAttribute
    {
        return $this->eavConfig->getAttribute('catalog_product', $attrCode);
    }

    public function getAttrSetId(string $attrSetName): int
    {
        $attributeSetCollection = $this->attributeSetCollection->create()
            ->addFieldToSelect('attribute_set_id')
            ->addFieldToFilter('attribute_set_name', $attrSetName)
            ->getFirstItem()
            ->toArray();

        return (int) $attributeSetCollection['attribute_set_id'];
    }

    public function createSpodAttributeSet(ModuleDataSetupInterface $setup): void
    {
        /** @var CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);

        $entityTypeId = $categorySetup->getEntityTypeId(Product::ENTITY);
        $defaultAttributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

        /** @var AttributeSetInterface $attributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $attributeSet->setData([
            'attribute_set_name' => 'SPOD',
            'entity_type_id' => $entityTypeId,
            'sort_order' => 200,
        ]);
        $attributeSet->validate();
        $this->attributeSetRepository->save($attributeSet);

        $attributeSet->initFromSkeleton($defaultAttributeSetId);
        $this->attributeSetRepository->save($attributeSet);
    }

    public function createConfigurableAttribute(ModuleDataSetupInterface $setup, string $label, string $code): void
    {
        $options = [
            'input' => 'select',
            'type' => 'int',
            'label' => $label,
            'required' => false,
            'user_defined' => true,
            'searchable' => true,
            'filterable' => true,
            'comparable' => true,
            'visible_in_advanced_search' => true,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => true,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'apply_to' => implode(',', [Type::TYPE_SIMPLE, Type::TYPE_VIRTUAL])
        ];

        $this->createAttribute($setup, $code, $options);
    }

    public function createTextAttribute(ModuleDataSetupInterface $setup, string $label, string $code): void
    {
        $options = [
            'type' => 'varchar',
            'label' => $label,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'global' => ScopedAttributeInterface::SCOPE_STORE
        ];

        $this->createAttribute($setup, $code, $options);
    }

    public function createYesNoAttribute(ModuleDataSetupInterface $setup, string $label, string $code): void
    {
        $options = [
            'type' => 'int',
            'label' => $label,
            'input' => 'boolean',
            'source' => Boolean::class,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => false
        ];

        $this->createAttribute($setup, $code, $options);
    }

    /**
     * Creates Product Attribute, SPOD Attribute Group and assigns SPOD group to SPOD Attribute Set
     *
     * @param ModuleDataSetupInterface $setup
     * @param string $code
     * @param array $attrData
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function createAttribute(ModuleDataSetupInterface $setup, string $code, array $attrData): void
    {
        /** @var CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
        $categorySetup->addAttribute(Product::ENTITY, $code, $attrData);
        $categorySetup->addAttributeGroup(Product::ENTITY, 'SPOD', 'SPOD');
        $categorySetup->addAttributeToSet(Product::ENTITY, 'SPOD', 'SPOD', $code);
    }

    /**
     * Get existing option values in the required format.
     *
     * @param AbstractAttribute|null $sizeAttr
     * @return array
     */
    public function getPreparedOptionValues(?AbstractAttribute $attr): array
    {
        // reload of class required, to get newly created options
        $objectManager = ObjectManager::getInstance();
        $reloadedAttrObj = $objectManager->create('Magento\Catalog\Model\ResourceModel\Eav\Attribute');
        $reloadedAttr = $reloadedAttrObj->load($attr->getId());

        $attrValues = [];
        $options = $reloadedAttr->getOptions();
        foreach ($options as $option) {
            if ($option->getLabel() == '' || $option->getValue() == '') {
                continue;
            }

            $attrValues[] = [
                'label' => $option->getLabel(),
                'attribute_id' => $attr->getId(),
                'value_index' => $option->getValue(),
            ];
        }

        return $attrValues;
    }
}
