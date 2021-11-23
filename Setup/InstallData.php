<?php

declare(strict_types=1);

namespace Spod\Sync\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Spod\Sync\Helper\AttributeHelper;

/**
 * Magento Setup class which creates the SPOD attribute set
 * and adds required attributes.
 *
 * @package Spod\Sync\Setup
 */
class InstallData implements InstallDataInterface
{
    /** @var AttributeHelper */
    private $attributeHelper;

    public function __construct(AttributeHelper $attributeHelper)
    {
        $this->attributeHelper = $attributeHelper;
    }

    /**
     * @inheirtDoc
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->attributeHelper->createSpodAttributeSet($setup);
        $this->attributeHelper->createConfigurableAttribute($setup, 'Appearance', 'spod_appearance');
        $this->attributeHelper->createConfigurableAttribute($setup, 'Size', 'spod_size');
        $this->attributeHelper->createTextAttribute($setup, 'Product ID', 'spod_product_id');
        $this->attributeHelper->createTextAttribute($setup, 'ProductType ID', 'spod_product_type_id');
        $this->attributeHelper->createTextAttribute($setup, 'Appearance ID', 'spod_appearance_id');
        $this->attributeHelper->createTextAttribute($setup, 'Size ID', 'spod_size_id');

        $setup->endSetup();
    }
}
