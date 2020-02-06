<?php
namespace Kukharchuk\ProductImport\Model\Product;

class DataProvider extends \Magento\Catalog\Model\Product\DataProvider
{

    protected function getFieldsMap()
    {
        $fields = parent::getFieldsMap();
        $fields['content'][] = 'bearing_sketch'; // custom image field

        return $fields;
    }
}