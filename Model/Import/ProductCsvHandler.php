<?php

namespace Kukharchuk\ProductImport\Model\Import;

class ProductCsvHandler
{
    /**
     * Collection of publicly available stores
     *
     * @var \Magento\Store\Model\ResourceModel\Store\Collection
     */
    protected $_publicStores;
    /**
     * CSV Processor
     *
     * @var \Magento\Framework\File\Csv
     */
    protected $csvProcessor;
    /**
     * @var array
     */
    protected $attributeMap;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    protected $attributeRepository;
    /**
     * @var string
     */
    protected $idField;

    /**
     * ProductCsvHandler constructor.
     *
     * @param \Magento\Store\Model\ResourceModel\Store\Collection $storeCollection
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Catalog\Model\ProductFactory $_productFactory
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        \Magento\Store\Model\ResourceModel\Store\Collection $storeCollection,
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Catalog\Model\ProductFactory $_productFactory,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
    ) {
        $this->_publicStores = $storeCollection->setLoadDefault(false);
        $this->_productFactory = $_productFactory;
        $this->csvProcessor = $csvProcessor;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Import Tax Rates from CSV file
     *
     * @param array $file file info retrieved from $_FILES array
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importFromCsvFile($file)
    {
        if (!isset($file['tmp_name'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid file upload attempt.'));
        }
        $productsRawData = $this->csvProcessor->getData($file['tmp_name']);

        // first row of file represents headers
        $this->setAttributeMap(array_shift($productsRawData));
        $this->setIdField();
        // check if all imported columns have a proper attribute
        $this->checkAttributes();
        foreach ($productsRawData as $dataRow) {
            $this->_importProduct($dataRow);
        }
    }

    /**
     * @param array $fields
     */
    protected function setAttributeMap(array $fields)
    {
        $this->attributeMap = $fields;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function setIdField()
    {
        if (array_search('id', $this->attributeMap)) {
            $this->idField = 'id';
        } elseif (array_search('sku', $this->attributeMap)) {
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
        $notExistAttributes = [];
        foreach ($this->attributeMap as $attribute) {
            try {
                $attribute = $this->attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, $attribute);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $notExistAttributes[] = $attribute;
            }
        }
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

        $product = $this->_productFactory->create()->load($productData[$this->idField], $this->idField);
        $product->addData($productData);

        $product->save();
    }

    /**
     * @param $productData
     * @return array
     */
    protected function applyAttributeMap($productData)
    {
        $mappedData = [];
        foreach ($productData as $index => $data) {
            $mappedData[$this->attributeMap[$index]] = $data;
        }

        return $mappedData;
    }

    // TODO: need to delete
    protected function log($message)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/templog.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $logger->info($message);
    }
}
