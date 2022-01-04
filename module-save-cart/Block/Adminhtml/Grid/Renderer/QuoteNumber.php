<?php

namespace Vekeryk\SaveCart\Block\Adminhtml\Grid\Renderer;

class QuoteNumber extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Vekeryk\SaveCart\Helper\Data
     */
    private $saveCartHelper;

    private $cartFactory;

    /**
     * @param \Vekeryk\SaveCart\Helper\Data $saveCartHelper
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $cartFactory,
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Vekeryk\SaveCart\Helper\Data $saveCartHelper,
        \Magento\Quote\Api\Data\CartInterfaceFactory $cartFactory,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->saveCartHelper = $saveCartHelper;
        $this->cartFactory = $cartFactory;
    }

    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $quote = $this->cartFactory->create();
        $quote->setId($row->getId());
        return $this->saveCartHelper->getQuoteNumber($quote);
    }
}
