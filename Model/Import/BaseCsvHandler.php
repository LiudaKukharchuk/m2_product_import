<?php

namespace Kukharchuk\ProductImport\Model\Import;

class BaseCsvHandler
{
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
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    protected $attributeRepository;


    /**
     * ProductCsvHandler constructor.
     *
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
    ) {
        $this->csvProcessor = $csvProcessor;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @param array $file file info retrieved from $_FILES array
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importFromCsvFile($file)
    {
        if (!isset($file['tmp_name'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid file upload attempt.'));
        }
        $rawData = $this->csvProcessor->getData($file['tmp_name']);

        // first row of file represents headers
        $this->setAttributeMap(array_shift($rawData));
        return $rawData;
    }

    /**
     * @param array $fields
     */
    protected function setAttributeMap(array $fields)
    {
        $this->attributeMap = $fields;
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function getNotExistsAttributes(array $attributes)
    {
        $notExistAttributes = [];
        foreach ($attributes as $attribute) {
            try {
                $attribute = $this->attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, $attribute);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $notExistAttributes[] = $attribute;
            }
        }
        return $notExistAttributes;
    }

    /**
     * @param $entityData
     * @return array
     */
    protected function applyAttributeMap($entityData)
    {
        $mappedData = [];
        foreach ($entityData as $index => $data) {
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
