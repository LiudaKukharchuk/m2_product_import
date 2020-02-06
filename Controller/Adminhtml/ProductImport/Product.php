<?php

namespace Kukharchuk\ProductImport\Controller\Adminhtml\ProductImport;

use Magento\Framework\Controller\ResultFactory;

class Product extends \Kukharchuk\ProductImport\Controller\Adminhtml\Base
{
    /**
     * @var \Kukharchuk\ProductImport\Model\Import\ProductCsvHandler
     */
    protected $importHandler;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Kukharchuk\ProductImport\Model\Import\ProductCsvHandler $importHandler
    ) {
        parent::__construct($context, $fileFactory);
        $this->importHandler = $importHandler;
    }

    public function execute()
    {
        $importProductsFile = $this->getRequest()->getFiles('import_products_file');
        if ($this->getRequest()->isPost() && isset($importProductsFile['tmp_name'])) {
            try {

                $this->importHandler->importFromCsvFile($importProductsFile);

                $this->messageManager->addSuccess(__('Products has been imported.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__('Invalid file upload attempt'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRedirectUrl());

        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
                'Magento_ImportExport::import'
            ) && $this->_authorization->isAllowed(
                'Kukharchuk_ProductImport::product_import_action'
            );
    }
}
