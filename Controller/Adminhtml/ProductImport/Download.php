<?php

namespace Kukharchuk\ProductImport\Controller\Adminhtml\ProductImport;

class Download extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * Download constructor.
     *
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * Get possibility to download import sample file for user
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $type = $this->getRequest()->getParam('download_attribute_type');
        $value = 'code/Kukharchuk/ProductImport/test_data/attribute_with_' . $type . '_params.csv';
        $fileName = 'attribute_with_' . $type . '_params.csv';
        try {
            $this->fileFactory->create(
                $fileName,
                [
                    'type'  => 'filename',
                    'value' => $value
                ],
                \Magento\Framework\App\Filesystem\DirectoryList::APP, //basedir
                'application/octet-stream',
                ''                                                    // content length will be dynamically calculated
            );
        } catch (\Exception $exception) {
            $this->messageManager->addError($exception->getMessage());
        }

        return $this->resultRawFactory->create();
    }
}
