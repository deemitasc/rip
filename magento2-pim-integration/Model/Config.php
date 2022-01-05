<?php
namespace Ripen\PimIntegration\Model;

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
     * @return string
     */
    public function getAttributePrefix()
    {
        return $this->scopeConfig->getValue('pim/sync/magento_attribute_prefix');
    }

    /**
     * @return string
     */
    public function getTopLevelPimCategoryId()
    {
        return (string) $this->scopeConfig->getValue('pim/sync/top_level_pim_category_id', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string[]
     */
    public function getPimCategoryIdsToIgnore()
    {
        $ignoredCategories =  (string) $this->scopeConfig->getValue('pim/sync/pim_ignored_category_ids', ScopeInterface::SCOPE_STORE);
        $ignoredCategories = explode(',', $ignoredCategories);
        $ignoredCategories = array_filter($ignoredCategories);
        array_push($ignoredCategories, $this->getTopLevelPimCategoryId());
        array_unique($ignoredCategories);

        return $ignoredCategories;
    }
}
