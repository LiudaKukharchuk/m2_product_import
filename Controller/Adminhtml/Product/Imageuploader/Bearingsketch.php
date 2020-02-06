<?php

namespace Kukharchuk\ProductImport\Controller\Adminhtml\Product\Imageuploader;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class Upload
 */
class Bearingsketch extends \Magento\Catalog\Controller\Adminhtml\Category\Image\Upload
{
    /**
     * Image uploader
     *
     * @var \Magento\Catalog\Model\ImageUploader
     */
    protected $imageUploader;

    /**
     * Check admin permissions for this controller
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Catalog::product');
    }

    /**
     * Upload file controller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->log('start');
        try {
            $result = $this->imageUploader->saveFileToTmpDir('product_bearing_sketch');
            $result['cookie'] = [
                'name'     => $this->_getSession()->getName(),
                'value'    => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path'     => $this->_getSession()->getCookiePath(),
                'domain'   => $this->_getSession()->getCookieDomain(),
            ];
            $this->log(implode(' ',$result));
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }

    protected function log($message)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/templog3.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $logger->info($message);
    }
}