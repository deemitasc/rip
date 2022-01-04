<?php

namespace Ripen\OrderCustomAttributes\Observer;

class SaveCustomAttributes implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        $order->setData('comments', $quote->getData('comments'));
        $order->setData('po_number', $quote->getData('po_number'));
        $order->setData('ship_entire_only', $quote->getData('ship_entire_only'));

        return $this;
    }
}
