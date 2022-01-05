<?php
namespace Ripen\PimIntegration\Model\Sync;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Ripen\PimIntegration\Logger\Logger;
use Ripen\PimIntegration\Model\DataParserInterface as DataParser;

class CategoriesSync
{
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        CategorySync $categorySync,
        DataParser $dataParser,
        CollectionFactory $categoryCollectionFactory,
        Registry $registry
    ) {
        $this->categorySync = $categorySync;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->dataParser = $dataParser;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->registry = $registry;
    }

    /**
     * @param $categories
     */
    public function run($categories)
    {
        $this->logger->info('Syncing categories...');
        if (!$this->scopeConfig->getValue('pim/sync/enable_category_sync')) {
            $this->logger->info('Interrupted. Category sync is disabled in admin settings.');
            return;
        }

        if (!is_array($categories)) {
            $categories = [$categories];
        }

        $pimCategoryIds = [];
        foreach ($categories as $category) {
            $pimCategoryIds[] = $this->dataParser->parseCategoryId($category);
            $this->categorySync->processCategory($category);
        }

        $this->logger->info('Sanitizing Magento categories...');
        $magentoCategoriesToRemove = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToFilter('pim_category_id', ['nin' => $pimCategoryIds, 'notnull' => true])
            ->getItems();

        $this->registry->register('isSecureArea', true);
        foreach ($magentoCategoriesToRemove as $category) {
            $this->logger->info("Remove category [{$category->getPimCategoryId()}][{$category->getCategoryName()}]");
            $category->delete();
        }

        $this->logger->info('Syncing categories completed');
    }
}
