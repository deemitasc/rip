<?php
namespace Ripen\ExclusiveProducts\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Ripen\ExclusiveProducts\Model\Customer as CustomerHelper;

class RestrictPurchase implements ObserverInterface
{
    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @param CustomerHelper $customerHelper
     */
    public function __construct(
        CustomerHelper $customerHelper
    ) {
        $this->customerHelper = $customerHelper;
    }

    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getData('product');

        if (! $this->customerHelper->isCustomerAllowedOnProduct($product)) {
            throw new LocalizedException(
                __("You do not have access to purchase that product.")
            );
        }
    }
}
