<?php

declare(strict_types=1);

namespace Spod\Sync\Setup;

use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Spod\Sync\Helper\AttributeHelper;

/**
 * Magento Setup class which adds additional attributes
 * during updates (after the initial setup).
 *
 * @package Spod\Sync\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /** @var AttributeHelper */
    private $attributeHelper;

    /** @var CategorySetupFactory */
    private $categorySetupFactory;

    public function __construct(
        AttributeHelper $attributeHelper,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $this->attributeHelper->createYesNoAttribute($setup, 'SPOD Produkt', 'spod_product');
        }

        if (version_compare($context->getVersion(), '1.2.0') < 0) {
            /** @var CategorySetup $categorySetup */
            $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $defaultAttributeSetId = $categorySetup->getDefaultAttributeSetId(\Magento\Catalog\Model\Product::ENTITY);
            $categorySetup->removeAttributeGroup(
                \Magento\Catalog\Model\Product::ENTITY,
                $defaultAttributeSetId,
                'SPOD'
            );
        }

        $setup->endSetup();
    }
}
