<?php
namespace Ripen\OrderCustomAttributes\Block\Order;

use Magento\Sales\Model\Order;

class ShipEntireOnly extends \Magento\Framework\View\Element\Template
{
    protected $coreRegistry = null;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->_isScopePrivate = true;
        $this->_template = 'order/view/ship_entire_only.phtml';
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    public function getOrderShipEntireOnly()
    {
        return trim($this->getOrder()->getData('ship_entire_only'));
    }

    public function hasOrderShipEntireOnly()
    {
        return strlen($this->getShipEntireOnly()) > 0;
    }

    public function getOrderShipEntireOnlyHtml()
    {
        return $this->escapeHtml($this->getShipEntireOnly());
    }
}
