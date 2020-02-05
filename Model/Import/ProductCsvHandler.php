<?php

namespace Kukharchuk\ProductImport\Model\Import;

class ProductCsvHandler extends BaseCsvHandler
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;
    /**
     * @var string
     */
    protected $idField;

    /**
     * ProductCsvHandler constructor.
     *
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
    ) {
        parent::__construct($csvProcessor, $attributeRepository);
        $this->productFactory = $productFactory;
    }

    /**
     * @param array $file file info retrieved from $_FILES array
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importFromCsvFile($file)
    {
        $productsRawData = parent::importFromCsvFile($file);

        $this->setIdField();
        // check if all imported columns have a proper attribute
        $this->checkAttributes();
        foreach ($productsRawData as $dataRow) {
            $this->_importProduct($dataRow);
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function setIdField()
    {
        if (array_search('id', $this->attributeMap) !== false) {
            $this->idField = 'id';
        } elseif (array_search('sku', $this->attributeMap) !== false) {
            $this->idField = 'sku';
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("There are no `id` or `sku` field in the file.")
            );
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkAttributes()
    {
        $notExistAttributes = $this->getNotExistsAttributes($this->attributeMap);
        if (count($notExistAttributes) >= 1) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("There are no attributes with codes: " .
                    implode(', ', $notExistAttributes) .
                    ". <b>Please create attribute/s or check imported file.</b>")
            );
        }
    }

    /**
     * @param array $productData
     * @throws \Exception
     */
    protected function _importProduct(array $productData)
    {
        $productData = $this->applyAttributeMap($productData);
        $name = 'Cur bromium unda.' . rand(1, 3000);
        $product = $this->productFactory->create()->load($productData[$this->idField], $this->idField);
        $product->addData($productData);
        $product->setAttributeSetId(4);
        $product->setName($name);
        $product->setPrice(12.33);
        $product->setTypeId('simple');
        $product->setVisibility(4);
        $product->setWebsiteIds([1]);
        $product->setStockData(array(
                'use_config_manage_stock' => 0, //'Use config settings' checkbox
                'manage_stock'            => 1, //manage stock
                'min_sale_qty'            => 1, //Minimum Qty Allowed in Shopping Cart
                'is_in_stock'             => 1, //Stock Availability
                'qty'                     => 100 //qty
            )
        );
        $product->setTaxClassId(2);
        $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);


        $product->save();
    }
}
