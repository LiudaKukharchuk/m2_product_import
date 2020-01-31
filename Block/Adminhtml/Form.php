<?php

namespace Kukharchuk\ProductImport\Block\Adminhtml;

class Form extends \Magento\Backend\Block\Widget
{
    /**
     * @var string
     */
    protected $_template = 'Kukharchuk_ProductImport::import.phtml';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->setUseContainer(true);
    }
}
