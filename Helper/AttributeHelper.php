<?php

namespace Spod\Sync\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\AttributeSetRepository;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class AttributeHelper extends AbstractHelper
{
    private $attributeSetCollection;
    private $attributeSetFactory;
    private $attributeSetRepository;
    private $categorySetupFactory;
    private $eavConfig;
    private $eavSetupFactory;
    private $setup;
    private $attributeResource;

    public function __construct(
        AttributeSetFactory $attributeSetFactory,
        AttributeSetRepository $attributeSetRepository,
        CategorySetupFactory $categorySetupFactory,
        CollectionFactory $attributeSetCollection,
        Config $eavConfig,
        EavSetupFactory $eavSetupFactory,
        Context $context
    ) {
        $this->attributeSetCollection = $attributeSetCollection;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->eavConfig = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;

        return parent::__construct($context);
    }

    public function getAttributeByCode($attrCode)
    {
        return $this->eavConfig->getAttribute('catalog_product', $attrCode);
    }

    public function getAttrSetId($attrSetName): int
    {
        $attributeSetCollection = $this->attributeSetCollection->create()
            ->addFieldToSelect('attribute_set_id')
            ->addFieldToFilter('attribute_set_name', $attrSetName)
            ->getFirstItem()
            ->toArray();

        return (int)$attributeSetCollection['attribute_set_id'];
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createSpodAttributeSet(): void
    {
        if (!$this->getSetup()) {
            throw new \Exception("Setup was not set");
        }

        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->setup]);

        $attributeSet = $this->attributeSetFactory->create();
        $entityTypeId = $categorySetup->getEntityTypeId(Product::ENTITY);
        $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);
        $data = [
            'attribute_set_name' => 'SPOD',
            'entity_type_id' => $entityTypeId,
            'sort_order' => 200,
        ];
        $attributeSet->setData($data);
        $attributeSet->validate();
        $this->attributeSetRepository->save($attributeSet);

        $attributeSet->initFromSkeleton($attributeSetId);
        $this->attributeSetRepository->save($attributeSet);
    }

    /**
     * Add a specific eav attribute
     *
     * @param $eavSetup
     */
    public function createConfigurableAttribute($label, $code): void
    {
        if (!$this->getSetup()) {
            throw new \Exception("Setup was not set");
        }

        $options = [
            'attribute_set_id' => 'SPOD',
            'group' => 'SPOD',
            'input' => 'select',
            'type' => 'int',
            'label' => $label,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'searchable' => true,
            'filterable' => true,
            'comparable' => false,
            'visible_on_front' => true,
            'visible_in_advanced_search' => true,
            'is_used_in_grid' => true,
            'is_html_allowed_on_front' => false,
            'used_for_promo_rules' => true,
            'frontend_class' => '',
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'unique' => false
        ];

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            $code,
            $options
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function removeAttribute(string $code): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        $eavSetup->removeAttribute(
            Product::ENTITY,
            $code
        );
    }

    /**
     * Add a specific eav attribute
     *
     * @param $eavSetup
     */
    public function createTextAttribute($label, $code): void
    {
        if (!$this->getSetup()) {
            throw new \Exception("Setup was not set");
        }

        $options = [
            'attribute_set_id' => 'SPOD',
            'group' => 'SPOD',
            'type' => 'varchar',
            'label' => $label,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'visible_in_advanced_search' => false,
            'is_used_in_grid' => true,
            'is_html_allowed_on_front' => false,
            'used_for_promo_rules' => false,
            'frontend_class' => '',
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'unique' => false
        ];

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            $code,
            $options
        );
    }

    /**
     * @param $label
     * @param $code
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function createYesNoAttribute($label, $code): void
    {
        if (!$this->getSetup()) {
            throw new \Exception("Setup was not set");
        }

        $options = [
            'group' => 'SPOD',
            'type' => 'int',
            'backend' => '',
            'frontend' => '',
            'label' => $label,
            'input' => 'boolean',
            'class' => '',
            'source' => Boolean::class,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'default' => '1',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => false,
            'unique' => false,
            'apply_to' => 'simple,configurable,virtual,bundle,downloadable'
        ];

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            $code,
            $options
        );
    }

    /**
     * @return ModuleDataSetupInterface
     */
    public function getSetup()
    {
        return $this->setup;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    public function setSetup(ModuleDataSetupInterface $setup): void
    {
        $this->setup = $setup;
    }

    /**
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
