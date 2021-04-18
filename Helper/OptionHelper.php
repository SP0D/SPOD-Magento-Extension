<?php

namespace Spod\Sync\Helper;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;

/**
 * Helps with the programmatic creation and
 * validation of attribute options.
 *
 * @package Spod\Sync\Helper
 */
class OptionHelper extends AbstractHelper
{
    /** @var Config  */
    private $eavConfig;
    /** @var EavSetup  */
    private $eavSetup;

    /**
     * OptionHelper constructor.
     *
     * @param Context $context
     * @param Config $eavConfig
     * @param EavSetup $eavSetup
     */
    public function __construct(
        Context $context,
        Config $eavConfig,
        EavSetup $eavSetup
    ) {
        $this->eavConfig = $eavConfig;
        $this->eavSetup = $eavSetup;
        parent::__construct($context);
    }

    /**
     * Adds new options to an existing attribute
     * with the given attribute code.
     *
     * @param $attributeCode
     * @param array $options
     */
    public function addOptionToAttribute($attributeCode, array $options)
    {
        foreach ($options as $option) {
            if ($this->optionExists($attributeCode, $option)) {
                continue;
            } else {
                $this->saveNewOption($attributeCode, $option);
            }
        }
    }

    /**
     * Reads the internal attribute value of a given option label,
     * which is required to set the actual value.
     *
     * @param $attributeCode
     * @param $label
     * @return false|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDropdownOptionValueForLabel($attributeCode, $label)
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);

        // reload required to catch new options
        $objManager = ObjectManager::getInstance();
        $sourceModel = $objManager->create('Magento\Eav\Model\Entity\Attribute\Source\Table');
        $sourceModel->setAttribute($attribute);
        $options = $sourceModel->getAllOptions(false);

        foreach ($options as $option) {
            if ($option['label'] == $label) {
                return $option['value'];
            }
        }

        return false;
    }

    /**
     * Check wether a certain option label already exists.
     *
     * @param $attributeCode
     * @param $option
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function optionExists($attributeCode, $option)
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
        $existingOptions = $attribute->getSource()->getAllOptions();

        foreach ($existingOptions as $id => $existingOption) {
            if ($existingOption['label'] == $option) {
                return true;
            }
        }

        return false;
    }

    /**
     * Saves a new option label in the database.
     *
     * @param $attributeCode
     * @param $option
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function saveNewOption($attributeCode, $option)
    {
        if (strlen($option) == 0) {
            return;
        }

        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
        $importOption = ['attribute_id' => $attribute->getAttributeId()];
        $importOption['value'][$this->generateOptionKey($option)][0] = trim($option);
        $this->eavSetup->addAttributeOption($importOption);
    }

    /**
     * Creates an internal option key, to prevent issues
     * with values like 4XXL (leading digit).
     *
     * @param $label
     * @return string
     */
    private function generateOptionKey($label)
    {
        $key = trim($label);
        $key = str_replace(' ', '', $key);
        $key = strtolower($key);

        // opt_ prefix to prevent issues with values
        // like 4XL (starting with numeric char)
        return sprintf("opt_%s", $key);
    }
}
