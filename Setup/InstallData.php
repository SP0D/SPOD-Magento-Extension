<?php

namespace Spod\Sync\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Spod\Sync\Helper\AttributeHelper;

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

        $setup->endSetup();
    }
}
