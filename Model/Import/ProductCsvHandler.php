<?php

namespace Kukharchuk\ProductImport\Model\Import;
use Magento\Catalog\Api\ProductRepositoryInterface;
class ProductCsvHandler
{
    /**
     * Collection of publicly available stores
     *
     * @var \Magento\Store\Model\ResourceModel\Store\Collection
     */
    protected $_publicStores;

    protected $productRepository;
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
     * @param \Magento\Store\Model\ResourceModel\Store\Collection $storeCollection
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\File\Csv $csvProcessor
     */
    public function __construct(
        \Magento\Store\Model\ResourceModel\Store\Collection $storeCollection,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\File\Csv $csvProcessor
    ) {
        // prevent admin store from loading
        $this->_publicStores = $storeCollection->setLoadDefault(false);
        $this->productRepository = $productRepository;
        $this->csvProcessor = $csvProcessor;
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
        $fileFields = $productsRawData[0];
        $this->createAttributeMap($fileFields);
        // store cache array is used to quickly retrieve store ID when handling locale-specific tax rate titles
        foreach ($productsRawData as $rowIndex => $dataRow) {
            // skip headers
            if ($rowIndex == 0) {
                continue;
            }
            $this->_importProduct($dataRow);
        }
    }

    /**
     * @param array $fields
     */
    protected function createAttributeMap(array $fields)
    {
        $this->attributeMap = $fields;
    }

    /**
     * @param array $productData
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _importProduct(array $productData)
    {

            $modelData = [
                'tax_country_id' => $productData[1],
                'rate'           => $productData[4],
                'zip_is_range'   => $productData[5],
                'zip_from'       => $productData[6],
                'zip_to'         => $productData[7],
            ];
            // try to load existing rate
        $productId=1;
         $product = $this->productRepository->getById($productId);
//        $product->addData($modelData);



        $product->setTitle('$rateTitles');
        $product->save();

    }
}
