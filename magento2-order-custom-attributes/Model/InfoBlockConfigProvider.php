<?php

namespace Ripen\OrderCustomAttributes\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Store\Model\ScopeInterface;

class InfoBlockConfigProvider implements ConfigProviderInterface
{

    const CHECKOUT_SPECIALNOTE_ENABLED = 'checkout/options/checkout_specialnote_enabled';

    protected $_scopeConfiguration;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->_scopeConfiguration = $scopeConfiguration;
        $this->layout = $layout;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $myBlockId = "checkout_message";
        $enabled = $this->_scopeConfiguration->getValue(self::CHECKOUT_SPECIALNOTE_ENABLED, ScopeInterface::SCOPE_WEBSITE);
        $displayConfig['show_info_block'] = ($enabled) ? true : false;
        $displayConfig['info'] = $this->layout->createBlock('Magento\Cms\Block\Block')->setBlockId($myBlockId)->toHtml();

        return $displayConfig;
    }
}