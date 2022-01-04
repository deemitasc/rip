<?php
/**
 * Config class to fetch all CMS-based config values for the module
 */

namespace Ripen\SearchHistory\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isSearchLogViewable()
    {
        return (bool)$this->scopeConfig->getValue('customer/search_history/enable_search_log_view', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function isGuestSearchLoggingEnabled()
    {
        return (bool)$this->scopeConfig->getValue('customer/search_history/enable_guest_search_logging', ScopeInterface::SCOPE_STORE);
    }
}
