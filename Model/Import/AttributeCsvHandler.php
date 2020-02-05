<?php

namespace Kukharchuk\ProductImport\Model\Import;

class AttributeCsvHandler extends BaseCsvHandler
{
    /**
     * @var array
     */
    protected $newAttributes = [];
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    protected $attributeFactory;
    protected $groupFactory;

    /**
     * AttributeCsvHandler constructor.
     *
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Magento\Catalog\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Catalog\Model\Product\Attribute\GroupFactory $groupFactory
    ) {
        parent::__construct($csvProcessor, $attributeRepository);
        $this->attributeFactory = $attributeFactory;
        $this->groupFactory = $groupFactory;
    }

    /**
     * @param array $file file info retrieved from $_FILES array
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importFromCsvFile($file)
    {
        $attributeRawData = parent::importFromCsvFile($file);
        $this->checkRequiredParam();
        $attributeCodes = [];
        foreach ($attributeRawData as $raw) {
            $attributeCodes[] = $raw[0];
        }
        $this->newAttributes = $this->getNotExistsAttributes($attributeCodes);

        return $this->_importAttributes($attributeRawData);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkRequiredParam()
    {
        $requiredParams = ['attribute_code', 'frontend_label', 'backend_type', 'frontend_input'];
        foreach ($requiredParams as $param) {
            if (array_search($param, $this->attributeMap) === false) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("There is no column with parameter: <b>%1</b>. Please fix imported file and try again.", $param)
                );
            }
        }
    }

    /**
     * @param array $attributesData
     * @return array
     * @throws \Exception
     */
    protected function _importAttributes(array $attributesData)
    {
        $createdCount = 0;
        $ignoredCount = 0;
        foreach ($attributesData as $attributeData) {
            $attributeData = $this->applyAttributeMap($attributeData);
            if (array_search($attributeData['attribute_code'], $this->newAttributes) !== false && $attributeData['attribute_code']) {
                $this->createAttribute($attributeData);
                $createdCount++;
            } else {
                $ignoredCount++;
            }
        }

        return [
            'created' => $createdCount,
            'ignored' => $ignoredCount
        ];
    }

    /**
     * @param array $attributeData
     * @throws \Exception
     */
    protected function createAttribute(array $attributeData)
    {
        $attribute = $this->attributeFactory->create();
        $attribute = $this->setDefaultParams($attribute);

        foreach ($attributeData as $param => $value) {
            $attribute->setData($param, $value);
        }
        $attribute->save();
    }

    /**
     * @param \Magento\Catalog\Model\Entity\Attribute $attribute
     * @return \Magento\Catalog\Model\Entity\Attribute
     */
    protected function setDefaultParams(\Magento\Catalog\Model\Entity\Attribute $attribute)
    {
        return $attribute
            ->setBackendModel('')
            ->setAttributeGroupId($this->getAttributeGroupId())
            ->setAttributeSetId($this->getAttributeSetId())
            ->setFrontendClass('')
            ->setEntityTypeId($this->getEntityTypeId())
            ->setIsRequired(false)
            ->setDefaultValue('')
            ->setIsUnique(false)
            ->setOptions()
            ->setIsUserDefined(0)
            ->setFrontendLabels()
            ->setNote('')
            ->setSourceModel('')
            ->setValidationRules()
            ->setValidateRules('')
            ->setIsVisible(1)
            ->setIsSearchable(1)
            ->setSearchWeight()
            ->setIsFilterable(1)
            ->setIsComparable(1)
            ->setIsVisibleOnFront(1)
            ->setIsHtmlAllowedOnFront(0)
            ->setIsUsedForPriceRules(0)
            ->setIsFilterableInSearch(0)
            ->setUsedInProductListing(0)
            ->setUsedForSortBy(0)
            ->setApplyTo('simple, configurable, grouped, virtual, bundle, downloadable')
            ->setIsVisibleInAdvancedSearch(1)
            ->setPosition('')
            ->setIsWysiwygEnabled(0)
            ->setIsUsedForPromoRules(0);
    }

    protected function getAttributeGroupId()
    {
        $attributeGroup = $this->groupFactory->create()->load('custom_attributes', 'attribute_group_code');
        if (!$attributeGroup->getAttributeGroupId()) {
            $attributeGroup = $this->createAttributeGroup();
        }

        return $attributeGroup->getAttributeGroupId();
    }

    // TODO: Fix this hard code
    protected function getAttributeSetId()
    {
        return 4; // Default product attribute set id
    }

    // TODO: Fix this hard code
    protected function getEntityTypeId()
    {
        return 4; // catalog_product entity type id
    }

    protected function createAttributeGroup()
    {
        $attributeGroup = $this->groupFactory->create();
        $attributeGroup->setEntityTypeId($this->getEntityTypeId())
            ->setAttributeSetId($this->getAttributeSetId())
            ->setAttributeGroupName('Custom Attributes')
            ->setAttributeGroupCode('custom_attributes');
        $attributeGroup->save();

        return $attributeGroup;
    }
}
