<?php
namespace Ripen\OrderCustomAttributes\Plugin\Block\Adminhtml;

class SalesOrderViewInfo
{
    public function afterToHtml(
        \Magento\Sales\Block\Adminhtml\Order\View\Info $subject,
        $result
    ) {
        $commentBlock = $subject->getLayout()->getBlock('order_comments');
        if ($commentBlock !== false && $subject->getNameInLayout() == 'order_info') {
            $commentBlock->setOrderComment($subject->getOrder()->getData('comments'));
            $result = $result . $commentBlock->toHtml();
        }
        $poNumberBlock = $subject->getLayout()->getBlock('order_po_number');
        if ($poNumberBlock !== false && $subject->getNameInLayout() == 'order_info') {
            $poNumberBlock->setOrderPoNumber($subject->getOrder()->getData('po_number'));
            $result = $result . $poNumberBlock->toHtml();
        }
        $shipEntireOnlyBlock = $subject->getLayout()->getBlock('order_ship_entire_only');
        if ($shipEntireOnlyBlock !== false && $subject->getNameInLayout() == 'order_info') {
            $shipEntireOnlyBlock->setOrderShipEntireOnly($subject->getOrder()->getData('ship_entire_only'));
            $result = $result . $shipEntireOnlyBlock->toHtml();
        }
        return $result;
    }
}
