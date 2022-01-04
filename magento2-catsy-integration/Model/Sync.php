<?php
namespace Ripen\CatsyIntegration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Ripen\PimIntegration\Logger\Logger;
use Ripen\PimIntegration\Model\Sync\AttributesSync;
use Ripen\PimIntegration\Model\Sync\CategoriesSync;
use Ripen\PimIntegration\Model\Sync\ProductSync;
use Magento\Indexer\Model\IndexerFactory;
use Ripen\PimIntegration\Model\DataParserInterface as DataParser;
use Ripen\PimIntegration\Model\AttributeMapper;

class Sync
{
    const PRODUCT_SYNC_BATCH_SIZE = 100;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CategoriesSync
     */
    protected $categoriesSync;

    /**
     * @var AttributesSync
     */
    protected $attributesSync;

    /**
     * @var ProductsSync
     */
    protected $productsSync;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Api $api
     * @param Logger $logger
     * @param CategoriesSync $categoriesSync
     * @param AttributesSync $attributesSync
     * @param ProductsSync $productsSync
     * @param IndexerFactory $indexerFactory
     * @param DataParser $dataParser
     * @param AttributeMapper $attributeMapper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Api $api,
        Logger $logger,
        CategoriesSync $categoriesSync,
        AttributesSync $attributesSync,
        ProductSync $productSync,
        IndexerFactory $indexerFactory,
        DataParser $dataParser,
        AttributeMapper $attributeMapper
    ) {
        $this->storeManager = $storeManager;
        $this->api = $api;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->categoriesSync = $categoriesSync;
        $this->attributesSync = $attributesSync;
        $this->productSync = $productSync;
        $this->indexerFactory = $indexerFactory;
        $this->attributeMapper = $attributeMapper;
        $this->dataParser = $dataParser;
    }

    /**
     * @return void
     */
    public function run()
    {
        if (!$this->scopeConfig->getValue('pim/sync/enable_data_sync')) {
            $this->logger->info('Data sync is disabled in admin settings.');
            return;
        }

        $this->logger->info('Syncing data...');

        // Sync all categories
        $this->categoriesSync->run($this->api->getCategories());

        // Sync all attributes
        $this->runAttributeSync();

        // Sync products by chunks
        $this->runProductSync();
    }

    /**
     * @return void
     */
    protected function runAttributeSync()
    {
        $attributes = $this->api->getAttributes();
        $cleanAttributes = [];

        // Ignore attributes that contain '$' in the key. It's only used internally by Catsy.
        foreach($attributes as $attribute) {
            $attributeCode = $this->dataParser->parseAttributeCode($attribute);
            if(strpos($attributeCode, '$') === false){
                $cleanAttributes[] = $attribute;
            }
        }

        $this->attributesSync->run($cleanAttributes);
    }

    /**
     * @return void
     */
    protected function runProductSync()
    {
        $this->logger->info('Syncing products...');

        if (!$this->scopeConfig->getValue('pim/sync/enable_product_sync')) {
            $this->logger->info('Interrupted. Product sync is disabled in admin settings.');
            return;
        }

        $batchIndex = 0;
        $productCount = 0;
        $filters = $this->getFilters();
        $i = 0;

        //$totalCount = $this->getTotalProductsCount();
        //$this->logger->info("Total products found [{$totalCount}]");

        do {
            $offset = $batchIndex * self::PRODUCT_SYNC_BATCH_SIZE;
            $params = array(
                'limit' => self::PRODUCT_SYNC_BATCH_SIZE,
                'offset' => $offset
            );
            $this->logger->info("Sync products [{$offset}][".self::PRODUCT_SYNC_BATCH_SIZE."]");

            try {
                $pimProducts = $this->api->getProducts($params, array("filters" => $filters));
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                $this->logger->error("API error while getting products: " . $e->getMessage());
                $this->logger->error("Filters: [$filters]");
            }

            foreach ($pimProducts as $pimProductData) {
                $i++;

                try{
                    if(
                        $this->dataParser->isSimpleProduct($pimProductData) ||
                        $this->dataParser->isParentProduct($pimProductData)
                    ){
                        $this->logger->info("Count [$i]");
                        $variants = $this->dataParser->isParentProduct($pimProductData) ? $this->getVariants($pimProductData) : [];
                        $type = $this->dataParser->parseProductType($pimProductData);
                        $this->productSync->processProduct($pimProductData, $type, $variants);
                    } else {
                        $this->logger->info("Count [$i] - skipping child [{$pimProductData[$this->attributeMapper->getPimAttributeCode('sku')]}]");
                    }
                } catch (\Exception $e) {
                    $this->logger->error("Error processing product [{$pimProductData[$this->attributeMapper->getPimAttributeCode('sku')]}] - " . $e->getMessage());
                }
            }
            $batchIndex++;

        } while (count($pimProducts) >= self::PRODUCT_SYNC_BATCH_SIZE && ($offset + self::PRODUCT_SYNC_BATCH_SIZE) <= $this->getProductImportLimit());

        $this->reindex();
        $this->logger->info('Syncing products completed');
    }

    /**
     * @return array
     */
    protected function getFilters()
    {
        $individualProductSkus = $this->scopeConfig->getValue('pim/sync/individual_product_sku_sync');
        $cutOffDay = $this->scopeConfig->getValue('pim/sync/days_to_sync') ? : 365;

        if ($individualProductSkus) {
            $individualProductSkus = str_replace(' ', '', $individualProductSkus);
            $individualProductSkus = str_replace(',', chr(10), $individualProductSkus);

            $filters[] = array(
                'attributeKey' => 'item_id',
                'operator' => 'is exactly bulk',
                'value' => $individualProductSkus
            );
        } else {
            $dateFrom = date('Y-m-d', strtotime('-'.$cutOffDay.' day'));
            $filters[] = array(
                'attributeKey' => 'update_date',
                'operator' => 'is',
                'lowerBoundValue' => $dateFrom
            );
        }

        if ($this->getSyncActiveOnly()) {
            $filters[] = array(
                'attributeKey' => 'product_web_status',
                'operator' => 'is',
                'value' => 'Active'
            );
        }

        // Temporarily - sync configurable products only
        /*
        $filters[] = array(
            'attributeKey' => 'product_group_sku',
            'operator' => 'is not empty'
        );
        */

        return $filters;
    }

    /**
     * @param array $productData
     * @return array
     */
    protected function getVariants($productData)
    {
        $filters[] =
            array(
                'attributeKey' => 'parent_item_id',
                'operator' => 'is',
                'value' => $productData[$this->attributeMapper->getPimAttributeCode('sku')]
            );

        $batchIndex = 0;
        $allChildProducts = [];

        do {
            $offset = $batchIndex * self::PRODUCT_SYNC_BATCH_SIZE;
            $params = array(
                'limit' => self::PRODUCT_SYNC_BATCH_SIZE,
                'offset' => $offset
            );

            try {
                $childProducts = $this->api->getChildren($params, array("filters" => $filters));
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                $this->logger->error("API error while getting child products: " . $e->getMessage());
                $this->logger->error("Filters: [$filters]");
            }

            $allChildProducts = array_merge($allChildProducts, $childProducts);

            $batchIndex++;

        } while (count($childProducts) >= self::PRODUCT_SYNC_BATCH_SIZE);


        return $allChildProducts;
    }

    /**
     * @return void
     */
    protected function reindex()
    {
        $indexer = $this->indexerFactory->create()->load('catalog_category_product');
        $indexer->reindexAll();
        $this->logger->info("Reindex completed");
    }

    /**
     * @return int
     */
    protected function getProductImportLimit()
    {
        $limit = intval($this->scopeConfig->getValue('pim/sync/product_import_limit'));
        return $limit ?: 10000000;
    }

    /**
     * @return bool
     */
    protected function getSyncActiveOnly()
    {
        return (bool) $this->scopeConfig->getValue('pim/sync/sync_active_only');
    }

    /**
     * @return int
     */
    protected function getTotalProductsCount()
    {
        $batchCount = 0;
        $batchIndex = 0;
        $filters = $this->getFilters();

        do {
            $offset = $batchIndex * self::PRODUCT_SYNC_BATCH_SIZE;
            $params = array(
                'limit' => self::PRODUCT_SYNC_BATCH_SIZE,
                'offset' => $offset
            );

            $pimProducts = $this->api->getProducts($params, array("filters" => $filters));
            $batchCount = $batchCount + count($pimProducts);

            $batchIndex++;

        } while (count($pimProducts) >= self::PRODUCT_SYNC_BATCH_SIZE);

        return $batchCount;
    }
}
