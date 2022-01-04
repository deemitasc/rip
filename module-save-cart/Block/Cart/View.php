<?php

namespace Vekeryk\SaveCart\Block\Cart;

use \Magento\Framework\App\ObjectManager;

class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'cart/view.phtml';

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Collection
     */
    private $quotes;

    /**
     * @var \Vekeryk\SaveCart\Model\SaveCart
     */
    private $saveCartModel;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Vekeryk\SaveCart\Model\SaveCart $saveCartModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->saveCartModel = $saveCartModel;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('My Saved Carts'));
    }

    /**
     * @return bool|\Magento\Quote\Model\ResourceModel\Quote\Collection
     */
    public function getQuotes()
    {
        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
            return false;
        }

        if (!$this->quotes) {
            $this->quotes = $this->saveCartModel->getSavedCartCollection($customerId);
        }
        return $this->quotes;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getQuotes()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'savecart.cart.view.pager'
            )->setCollection(
                $this->getQuotes()
            );
            $this->setChild('pager', $pager);
            $this->getQuotes()->load();
        }
        return $this;
    }

    /**
     * @param object $quote
     * @return string
     */
    public function getAddToCartUrl($quote)
    {
        return $this->getUrl('savecart/cart/put');
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @param object $quote
     * @return string
     */
    public function getViewUrl($quote)
    {
        return $this->getUrl('savecart/cart/edit', ['quote_id' => $quote->getId()]);
    }

    /**
     * @param object $quote
     * @return string
     */
    public function getPrintUrl($quote)
    {
        return $this->getUrl('savecart/cart/print', ['quote_id' => $quote->getId()]);
    }

    /**
     * @param object $quote
     * @return string
     */
    public function getDeleteCartUrl($quote)
    {
        return $this->getUrl('savecart/cart/put');
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('customer/account/');
    }

    /**
     * @param object $quote
     * @return bool
     */
    public function isCurrent($quote)
    {
        return $quote->getId() == $this->checkoutSession->getQuote()->getId();
    }
}
