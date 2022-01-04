<?php

namespace Vekeryk\SaveCart\Block\Adminhtml\Edit\Tab;

class Cart extends \Magento\Customer\Block\Adminhtml\Edit\Tab\Cart
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Magento_Customer::tab/cart.phtml');
    }

    public function getQuoteId()
    {
        return $this->_coreRegistry->registry('current_customer_id');
    }

    protected function getQuote()
    {
        if (null === $this->quote) {
            $quoteId = $this->getQuoteId();
            $storeIds = $this->_storeManager->getWebsite($this->getWebsiteId())->getStoreIds();

            try {
                $this->quote = $this->quoteRepository->get($quoteId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->quote = $this->quoteFactory->create()->setSharedStoreIds($storeIds);
            }
        }
        return $this->quote;
    }
}
