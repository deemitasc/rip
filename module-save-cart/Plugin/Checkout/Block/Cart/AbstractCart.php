<?php

namespace Vekeryk\SaveCart\Plugin\Checkout\Block\Cart;

class AbstractCart
{

    public function afterGetItemRenderer(\Magento\Checkout\Block\Cart\AbstractCart $subject, $result)
    {
        if ($subject->getRequest()->getActionName() == 'print') {
            $result->setTemplate('Vekeryk_SaveCart::cart/print/item/default.phtml');
        }
        return $result;
    }
}
