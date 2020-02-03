<?php

namespace Kukharchuk\ProductImport\Controller\Adminhtml\ProductImport;

use Magento\Framework\Controller\ResultFactory;

class Attribute extends \Kukharchuk\ProductImport\Controller\Adminhtml\Base
{
    /**
     * @var \Kukharchuk\ProductImport\Model\Import\AttributeCsvHandler
     */
    protected $importHandler;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Kukharchuk\ProductImport\Model\Import\AttributeCsvHandler $importHandler
    ) {
        parent::__construct($context, $fileFactory);
        $this->importHandler = $importHandler;
    }

    public function execute()
    {
        $importAttributeFile = $this->getRequest()->getFiles('import_attribute_file');
        if ($this->getRequest()->isPost() && isset($importAttributeFile['tmp_name'])) {
            try {
                $this->importHandler->importFromCsvFile($importAttributeFile);

                $this->messageManager->addSuccess(__('Attributes has been imported.'));
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
            ) || $this->_authorization->isAllowed(
                'Kukharchuk_ProductImport::product_import_import'
            );
    }
}
