<?php

namespace Ripen\OrderCustomAttributes\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Store\Model\ScopeInterface;

class PoNumberBlockConfigProvider implements ConfigProviderInterface
{
    const CHECKOUT_PO_NUMBER_ENABLED = 'checkout/options/checkout_po_number_enabled';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfiguration;

    /**
     * @var \Ripen\Prophet21\Helper\Customer
     */
    protected $customerHelper;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    protected $customerSession;

    /**
     * PoNumberBlockConfigProvider constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
     * @param \Ripen\Prophet21\Helper\Customer $customerHelper
     * @param \Magento\Customer\Model\SessionFactory $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration,
        \Ripen\Prophet21\Helper\Customer $customerHelper,
        \Magento\Customer\Model\SessionFactory $customerSession
    ) {
        $this->scopeConfiguration = $scopeConfiguration;
        $this->customerHelper = $customerHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        /** @var array() $displayConfig */
        $displayConfig = [];

        /** @var boolean $enabled */
        $enabled = $this->scopeConfiguration->getValue(self::CHECKOUT_PO_NUMBER_ENABLED, ScopeInterface::SCOPE_STORE);
        $displayConfig['show_po_number_block'] = ($enabled) ? true : false;
        $displayConfig['po_number_required'] = $this->customerHelper->isPoNumberRequiredForCustomerId($this->customerSession->create()->getCustomer()->getId());

        return $displayConfig;
    }
}
