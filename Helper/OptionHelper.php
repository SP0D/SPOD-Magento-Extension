<?php

namespace Spod\Sync\Helper;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;

class OptionHelper extends AbstractHelper
{
    private $eavConfig;
    private $eavSetup;

    public function __construct(
        Context $context,
        Config $eavConfig,
        EavSetup $eavSetup
    )
    {
        $this->eavConfig = $eavConfig;
        $this->eavSetup = $eavSetup;
        parent::__construct($context);
    }

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
