<?php
namespace Ripen\OrderCustomAttributes\Block\Order;

use Magento\Sales\Model\Order;

class PoNumber extends \Magento\Framework\View\Element\Template
{
    protected $coreRegistry = null;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->_isScopePrivate = true;
        $this->_template = 'order/view/po_number.phtml';
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    public function getOrderPoNumber()
    {
        return trim($this->getOrder()->getData('po_number'));
    }

    public function hasOrderPoNumber()
    {
        return strlen($this->getOrderPoNumber()) > 0;
    }

    public function getOrderPoNumberHtml()
    {
        return $this->escapeHtml($this->getOrderPoNumber());
    }
}
