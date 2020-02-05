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

    /**
     * AttributeCsvHandler constructor.
     *
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($csvProcessor, $attributeRepository);
        $this->objectManager = $objectManager;
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
            if (array_search($attributeData['attribute_code'], $this->newAttributes) !== false) {
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
        $attribute = $this->objectManager->create('\Magento\Catalog\Model\Entity\Attribute');
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
            ->setFrontendClass('')
            ->setEntityTypeId(4)
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
}
