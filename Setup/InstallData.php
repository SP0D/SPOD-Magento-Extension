<?php

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
    private $attributeHelper;

    public function __construct(
        AttributeHelper $attributeHelper
    ) {
        $this->attributeHelper = $attributeHelper;
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
        $setup->startSetup();

        $this->attributeHelper->setSetup($setup);
        $this->attributeHelper->createSpodAttributeSet();
        $this->attributeHelper->createConfigurableAttribute('Appearance', 'spod_appearance');
        $this->attributeHelper->createConfigurableAttribute('Size', 'spod_size');
        $this->attributeHelper->createTextAttribute('Product ID', 'spod_product_id');
        $this->attributeHelper->createTextAttribute('ProductType ID', 'spod_product_type_id');
        $this->attributeHelper->createTextAttribute('Appearance ID', 'spod_appearance_id');
        $this->attributeHelper->createTextAttribute('Size ID', 'spod_size_id');

        $setup->endSetup();
    }
}
