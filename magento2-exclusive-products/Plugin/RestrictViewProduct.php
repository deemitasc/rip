<?php
namespace Ripen\ExclusiveProducts\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Session;

class RestrictViewProduct
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @param Session $customerSession
     */
    public function __construct(
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * @param Product $product
     * @param bool $isVisibleInCatalog
     * @return bool
     */
    public function afterIsVisibleInCatalog(
        Product $product,
        $isVisibleInCatalog
    ) {
        if (! $isVisibleInCatalog) {
            return false;
        }

        // Only limit visibility of guest customers from exclusive products. We cannot enforce visibility beyond this
        // due to FPC being shared among logged-in users in the same customer group.
        if (! $this->customerSession->isLoggedIn()) {
            $exclusiveTo = $product->getCustomAttribute('exclusive_to');
            if ($exclusiveTo && ! empty($exclusiveTo->getValue())) {
                return false;
            }
        }

        return true;
    }
}
