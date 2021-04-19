<?php

namespace Spod\Sync\Setup;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
    /** @var AttributeHelper  */
    private $attributeHelper;

    public function __construct(
        AttributeHelper $attributeHelper
    ) {
        $this->attributeHelper = $attributeHelper;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->attributeHelper->setSetup($setup);

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $this->attributeHelper->createYesNoAttribute('SPOD Produkt', 'spod_product');
        }

        $setup->endSetup();
    }
}
