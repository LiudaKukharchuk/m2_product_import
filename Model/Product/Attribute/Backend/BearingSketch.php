<?php

namespace Kukharchuk\ProductImport\Model\Product\Attribute\Backend;


/**
 * Class Upload
 */
class BearingSketch extends \Magento\Catalog\Model\Product\Attribute\Frontend\Image
{
    /**
     * Returns url to product image
     *
     * @param  \Magento\Catalog\Model\Product $product
     *
     * @return string|false
     */
    public function getUrl($product)
    {
        $image = $product->getData($this->getAttribute()->getAttributeCode());
        $url = false;
        if (!empty($image)) {
            $url = $this->_storeManager
                    ->getStore($product->getStore())
                    ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product/bearing_sketch/' . ltrim($image, '/');
        }
        return $url;
    }
}