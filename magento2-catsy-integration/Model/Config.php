<?php
namespace Ripen\CatsyIntegration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config extends \Ripen\PimIntegration\Model\Config
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
        $baseUrl = (string) $this->scopeConfig->getValue('pim/api/base_url', ScopeInterface::SCOPE_STORE);

        if (empty($baseUrl)) {
            throw new \RuntimeException('PIM base endpoint URL not configured.');
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
    public function getApiKey()
    {
        $apiKey = (string) $this->scopeConfig->getValue('pim/api/api_key', ScopeInterface::SCOPE_STORE);

        if (empty($apiKey)) {
            throw new \RuntimeException('PIM API key not configured.');
        }

        return $apiKey;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        $basePath = (string) $this->scopeConfig->getValue('pim/api/base_path', ScopeInterface::SCOPE_STORE);

        if (empty($basePath)) {
            throw new \RuntimeException('PIM base path not configured.');
        }

        return $basePath;
    }
}
