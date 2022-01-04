<?php
namespace Ripen\OrderCustomAttributes\Block\Order;

use Magento\Sales\Model\Order;

class Comment extends \Magento\Framework\View\Element\Template
{
    protected $coreRegistry = null;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->_isScopePrivate = true;
        $this->_template = 'order/view/comment.phtml';
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    public function getOrderComment()
    {
        return trim($this->getOrder()->getData('comments'));
    }

    public function hasOrderComment()
    {
        return strlen($this->getOrderComment()) > 0;
    }

    public function getOrderCommentHtml()
    {
        return nl2br($this->escapeHtml($this->getOrderComment()));
    }
}