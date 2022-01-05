<?php
namespace Ripen\PimIntegration\Model\Sync;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Ripen\PimIntegration\Logger\Logger;
use Ripen\PimIntegration\Model\Config as PimConfig;
use Ripen\PimIntegration\Model\DataParserInterface as DataParser;
use Ripen\PimIntegration\Model\PimCategoryInterface as PimCategory;

class CategorySync
{
    const DEFAULT_PARENT_CATEGORY_ID = 2;
    const ALL_CATEGORY_NAME = 'All';
    const ALL_CATEGORY_URL_KEY = 'shop';

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var DataParser
     */
    protected $dataParser;

    /**
     * @var PimCategory
     */
    protected $pimCategory;

    /**
     * @var \Ripen\PimIntegration\Model\Config
     */
    protected $config;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        DataParser $dataParser,
        Logger $logger,
        PimCategory $pimCategory,
        PimConfig $config,
        CategoryFactory $categoryFactory,
        CategoryRepository $categoryRepository,
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->dataParser = $dataParser;
        $this->pimCategory = $pimCategory;
        $this->config = $config;
    }

    /**
     * @return int
     */
    public function getDefaultCategoryId()
    {
        return self::DEFAULT_PARENT_CATEGORY_ID;
    }

    /**
     * @param $pimCategory
     * @return int
     */
    public function processCategory($pimCategory)
    {
        try {
            $isUpdated = false;
            $pimCategoryId = $this->dataParser->parseCategoryId($pimCategory);
            $pimCategoryName = $this->dataParser->parseCategoryName($pimCategory);
            $pimCategoryPosition = $this->dataParser->parseCategoryPosition($pimCategory);
            $pimCategoryLevel = $this->dataParser->parseCategoryLevel($pimCategory);
            $pimCategoryParentId = $this->dataParser->parseCategoryParentId($pimCategory);
            $magentoCategoryParentId  = $this->getDefaultCategoryId();

            if (in_array($pimCategoryId, $this->config->getPimCategoryIdsToIgnore())) {
                return self::DEFAULT_PARENT_CATEGORY_ID;
            }

            if ($pimCategoryParentId && $pimCategoryParentId != $this->config->getTopLevelPimCategoryId()) {
                $magentoParentCategory = $this->categoryCollectionFactory
                    ->create()
                    ->addAttributeToFilter('pim_category_id', $pimCategoryParentId)
                    ->getFirstItem();
                if ($magentoParentCategory->getId()) {
                    $magentoCategoryParentId = $magentoParentCategory->getId();
                } else {
                    $pimParentCategory = $this->pimCategory->getById($pimCategoryParentId);
                    $magentoCategoryParentId = $this->processCategory($pimParentCategory);
                }
            }

            $magentoCategory = $this->categoryCollectionFactory
                ->create()
                ->addAttributeToFilter('pim_category_id', $pimCategoryId)
                ->getFirstItem();

            if ($magentoCategory->getId()) {
                $category = $this->categoryRepository->get($magentoCategory->getId());
                $magentoCategoryId = $magentoCategory->getId();

                // name changed
                if ($pimCategoryName != $category->getData('name')) {
                    $magentoCategory->setName($pimCategoryName);
                    $isUpdated = true;
                }
                // position changed
                if ($pimCategoryPosition != $category->getData('position')) {
                    $magentoCategory->setPosition($pimCategoryPosition);
                    $isUpdated = true;
                }
                if ($isUpdated) {
                    $this->categoryRepository->save($magentoCategory);
                }

                //parent category changed
                if ($magentoCategoryParentId != $category->getData('parent_id')) {
                    $category->move($magentoCategoryParentId, null);
                }
            } else {
                $this->logger->info("Create category [{$pimCategoryId}][{$pimCategoryName}]");
                $magentoCategoryId = $this->createCategory($pimCategoryName, $pimCategoryId, $magentoCategoryParentId, $pimCategoryPosition, $pimCategoryLevel);
            }
        } catch (\Exception $e) {
            $this->logger->error("Error updating category [{$pimCategoryName}] - " . $e->getMessage());
        }

        return $magentoCategoryId;
    }

    /**
     * @param $pimCategoryName
     * @param $pimCategoryId
     * @param $magentoCategoryParentId
     * @param $pimCategoryPosition
     * @param $pimCategoryLevel
     * @return mixed
     */
    protected function createCategory($pimCategoryName, $pimCategoryId, $magentoCategoryParentId, $pimCategoryPosition, $pimCategoryLevel)
    {
        $category = $this->categoryFactory->create();
        $category->setName($pimCategoryName);
        $category->setPimCategoryId($pimCategoryId);
        $category->setIsActive(1);
        $category->setParentId($magentoCategoryParentId);
        $category->setPosition($pimCategoryPosition);
        $this->categoryRepository->save($category);
        return $category->getId();
    }

    public function getMagentoAllCategoryId()
    {
        $categoryName = self::ALL_CATEGORY_NAME;

        $magentoCategory = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToFilter('name', $categoryName)
            ->getFirstItem();

        // check if exists
        if ($magentoCategory->getId()) {
            return $magentoCategory->getId();

        // create new using pim category name
        } else {
            $category = $this->categoryFactory->create();
            $category->setName($categoryName);
            $category->setIsActive(1);
            $category->setParentId($this->getDefaultCategoryId());
            $category->setUrlKey(self::ALL_CATEGORY_URL_KEY);
            $this->categoryRepository->save($category);
            return $category->getId();
        }
    }
}
