<?php

namespace Kukharchuk\ProductImport\Model\Import;

class ProductCsvHandler extends BaseCsvHandler
{
    protected $storeManager;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;
    /**
     * @var string
     */
    protected $idField;
    protected $requiredParams   = ['name', 'price', 'type_id'];
    protected $systemAttributes = [
        'type_id',
        'qty',
        'use_config_manage_stock',
        'manage_stock',
        'min_sale_qty',
        'max_sale_qty',
        'is_in_stock'
    ];
    private   $productRepository;
    protected $stockRegistry;

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
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
    ) {
        parent::__construct($csvProcessor, $attributeRepository);
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param array $file file info retrieved from $_FILES array
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importFromCsvFile($file)
    {
        $productsRawData = parent::importFromCsvFile($file);

        $this->checkRequiredParam();
        foreach ($productsRawData as $dataRow) {
            $this->_importProduct($dataRow);
        }
    }

    protected function checkRequiredParam()
    {
        $this->setIdField();
        // check if all imported columns have a proper attribute
        $this->checkAttributes();
        parent::checkRequiredParam();
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
        foreach ($this->systemAttributes as $attribute) {
            if (($key = array_search($attribute, $notExistAttributes)) !== false) {
                unset($notExistAttributes[$key]);
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
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function _importProduct(array $productData)
    {
        $productData = $this->applyAttributeMap($productData);
        $this->checkEmptyData($productData);

        $product = $this->getProduct($productData[$this->idField]);

        if (!$product->getId()) {
            $product = $this->setDefaultData($product, $productData['name']);
        }
        $product->addData($productData);
        $this->productRepository->save($product);
        $this->importStockData($product->getSku(), $productData);
    }

    protected function checkEmptyData($productData)
    {
        foreach (array_merge($this->requiredParams, [$this->idField]) as $param) {
            if (!$productData[$param]) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("Some product doesn't have data in column <b>`%1`</b>. Please check imported file.", $param)
                );
            }
        }
    }

    protected function getProduct($id)
    {
        try {
            return $this->idField == 'id' ? $this->productRepository->getById($id) : $this->productRepository->get($id);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $this->productFactory->create();
        }
    }

    protected function setDefaultData($product, $name)
    {
        $product->setAttributeSetId($this->getAttributeSetId());
        $product->setVisibility(4);
        $product->setWebsiteIds($this->getWebsitesIds());

        $product->setTaxClassId(2);
        $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $product->setMetaKeyword($name);
        $product->setMetaTitle($name);
        $product->setMetaDescription($name);

        return $product;
    }

    /**
     * @return \Magento\Store\Api\Data\WebsiteInterface[]
     */
    protected function getWebsitesIds()
    {
        $websites = $this->storeManager->getWebsites();
        $websitesId = [];
        foreach ($websites as $website) {
            $websitesId[] = $website->getId();
        }

        return $websitesId;
    }

    /**
     * @param string $sku
     * @param array $productData
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function importStockData($sku, $productData)
    {
        $stockItem = $this->stockRegistry->getStockItemBySku($sku);

        !isset($productData['use_config_manage_stock']) ?: $stockItem->setQty($productData['use_config_manage_stock']);
        !isset($productData['manage_stock']) ?: $stockItem->setQty($productData['manage_stock']);
        !isset($productData['min_sale_qty']) ?: $stockItem->setQty($productData['min_sale_qty']);
        !isset($productData['max_sale_qty']) ?: $stockItem->setQty($productData['max_sale_qty']);
        !isset($productData['is_in_stock']) ?: $stockItem->setQty($productData['is_in_stock']);
        !isset($productData['qty']) ?: $stockItem->setQty($productData['qty']);

        $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
    }
}
