<?php
namespace Spod\Sync\Setup;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\AttributeSetRepository;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private $attributeSetFactory;
    private $attributeSetRepository;
    private $categorySetupFactory;
    private $eavSetupFactory;
    private $setup;

    /**
     * InstallData constructor.
     *
     * @param AttributeSetFactory $attributeSetFactory
     * @param AttributeSetRepository $attributeSetRepository
     * @param CategorySetupFactory $categorySetupFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        AttributeSetFactory $attributeSetFactory,
        AttributeSetRepository $attributeSetRepository,
        CategorySetupFactory $categorySetupFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->setup = $setup;

        $setup->startSetup();
        $this->createSpodAttributeSet();
        $this->createSpodAttributes();
        $setup->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createSpodAttributeSet(): void
    {
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
     * Initiate attribute creation
     */
    private function createSpodAttributes(): void
    {
        $this->createConfigurableAttribute('Appearance', 'spod_appearance');
        $this->createConfigurableAttribute('Size', 'spod_size');
    }

    /**
     * Add a specific eav attribute
     *
     * @param $eavSetup
     */
    private function createConfigurableAttribute($label, $code): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            $code,
            [
                'attribute_set_id' => 'SPOD',
                'is_global' => 1,
                'label' => $label,
                'frontend_label' => $label,
                'frontend_input' => 'select',
                'is_unique' => 0,
                'apply_to' => 0,
                'is_required' => 1,
                'is_configurable' => 1,
                'is_searchable' => 0,
                'is_comparable' => 0,
                'is_visible_in_advanced_search' => 1,
                'is_used_for_price_rules' => 0,
                'is_wysiwyg_enabled' => 0,
                'is_html_allowed_on_front' => 1,
                'is_visible_on_front' => 0,
                'used_in_product_listing' => 0,
                'used_for_sort_by' => 0,
                'is_filterable' => 0,
                'is_filterable_in_search' => 0,
                'backend_type' => 'int',
            ]
        );
    }
}
