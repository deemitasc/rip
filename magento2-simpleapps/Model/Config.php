<?php
/**
 * Config class to fetch all CMS-based config values for the module
 */

namespace Ripen\SimpleApps\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use RuntimeException;

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
     * @return string
     * @throws \RuntimeException
     */
    public function getBaseUrl()
    {
        $baseUrl = (string) $this->scopeConfig->getValue('SimpleApps/api/base_url');

        if (empty($baseUrl)) {
            throw new RuntimeException('SimpleApps base endpoint URL not configured.');
        }

        // Enforce trailing slash for compatibility with RFC-3986 relative resolution
        // See: https://tools.ietf.org/html/rfc3986#section-5.2
        if (substr($baseUrl, -1) !== '/') {
            $baseUrl .= '/';
        }

        return $baseUrl;
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getXApiKey()
    {
        $apiKey = (string) $this->scopeConfig->getValue('SimpleApps/api/x_api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('SimpleApps API key not configured.');
        }

        return $apiKey;
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getSiteID()
    {
        $siteId = $this->scopeConfig->getValue('SimpleApps/api/site_id');

        if (empty($siteId)) {
            throw new RuntimeException('SimpleApps site ID not configured.');
        }

        return $siteId;
    }

    /**
     * @return bool
     */
    public function isDebugModeActive()
    {
        $debugModeTimeout = $this->scopeConfig->getValue('SimpleApps/api/debug_mode_timeout');

        if ($debugModeTimeout) {
            return strtotime($debugModeTimeout) > time();
        }

        return false;
    }
}
