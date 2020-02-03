<?php

namespace Kukharchuk\ProductImport\Model\Import;

class AttributeCsvHandler extends BaseCsvHandler
{
    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    protected $_attributeFactory;
    /**
     * @var \Magento\Catalog\Model\Entity\Attribute
     */
    protected $attribute;
    protected $newAttributes = [];

    /**
     *
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Eav\Model\AttributeFactory $_attributeFactory
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Catalog\Model\Entity\Attribute $attribute
     */
    public function __construct(
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Eav\Model\AttributeFactory $_attributeFactory,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Magento\Catalog\Model\Entity\Attribute $attribute
    ) {
        parent::__construct($csvProcessor, $attributeRepository);
        $this->_attributeFactory = $_attributeFactory;
        $this->attribute = $attribute;
    }

    /**
     * @param array $file file info retrieved from $_FILES array
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importFromCsvFile($file)
    {
        if (!isset($file['tmp_name'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid file upload attempt.'));
        }
        $attributeRawData = $this->csvProcessor->getData($file['tmp_name']);

        // first row of file represents headers
        $this->setAttributeMap(array_shift($attributeRawData));
        $attributeCodes = [];
        foreach ($attributeRawData as $raw)
        {
            $attributeCodes[] = $raw[0];
        }
        $this->newAttributes = $this->getNotExistsAttributes($attributeCodes);
        $this->log($this->newAttributes);
        foreach ($attributeRawData as $dataRow) {
                $this->_importAttribute($dataRow);
        }
    }

    /**
     * @param array $attributeData
     */
    protected function _importAttribute(array $attributeData)
    {

        $attributeData = $this->applyAttributeMap($attributeData);
        if (array_search($attributeData['code'], $this->newAttributes) !== false) {

//            $this->log($attributeData);

            $attribute = $this->attribute;
//        $this->attribute->setData([
//                'source'                  => '',
//                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
//                'visible'                 => true,
//                'user_defined'            => false,
//                'searchable'              => false,
//                'filterable'              => false,
//                'comparable'              => false,
//                'visible_on_front'        => false,
//                'used_in_product_listing' => true,
//                'apply_to'                => ''
//            ]
//        );
            $attribute->setAttributeCode('test');
            $attribute->setDefaultFrontendLabel('Genetrix de primus consilium, aperto vortex!')
                ->setFrontendInput('text')
                ->setBackendType('text')
                ->setBackendModel('')
                ->setFrontendClass('')
                ->setEntityTypeId(4)
                ->setIsRequired(false)
                ->setDefaultValue('qwer')
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
                ->setIsVisibleOnFront('')
                ->setIsHtmlAllowedOnFront('')
                ->setIsUsedForPriceRules('')
                ->setIsFilterableInSearch('')
                ->setUsedInProductListing('')
                ->setUsedForSortBy('')
                ->setApplyTo('simple')
                ->setIsVisibleInAdvancedSearch('')
                ->setPosition('')
                ->setIsWysiwygEnabled('')
                ->setIsUsedForPromoRules('');
//            $attribute->save();
        }else {
            $this->log($attributeData['code']);
        }

//        die();
    }
}
