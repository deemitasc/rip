<?php
namespace Ripen\PimIntegration\Model\Sync;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Model\Entity\Attribute\Source\TableFactory as AttributeSourceTableFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filter\FilterManager;
use Magento\Store\Model\StoreManagerInterface;
use Ripen\PimIntegration\Logger\Logger;
use Ripen\PimIntegration\Model\AttributeMapper;
use Ripen\PimIntegration\Model\Config;
use Ripen\PimIntegration\Model\DataFormatter;
use Ripen\PimIntegration\Model\DataParserInterface as DataParser;
use Ripen\PimIntegration\Model\PimCategoryInterface as PimCategory;
use Ripen\Prophet21\Helper\MultistoreHelper;
use Sparsh\ProductAttachment\Model\ProductAttachmentFactory;
use Sparsh\ProductAttachment\Model\ResourceModel\ProductAttachment\CollectionFactory as AttachmentCollectionFactory;

class ProductSync
{
    const ATTACHMENTS_DIRECTORY = '/sparsh/product_attachment';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute[]
     */
    protected $magentoAttributes;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var AttributeCollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * @var DataFormatter
     */
    protected $dataFormatter;

    /**
     * @var AttributeMapper
     */
    protected $attributeMapper;

    /**
     * @var AttributeOptionLabelInterfaceFactory
     */
    protected $optionLabelFactory;

    /**
     * @var AttributeOptionInterfaceFactory
     */
    protected $optionFactory;

    /**
     * @var AttributeOptionManagementInterface
     */
    protected $attributeOptionManagement;

    /**
     * @var AttributeSourceTableFactory
     */
    protected $attributeSourceTableFactory;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var DataParser
     */
    protected $dataParser;

    /**
     * @var CategoriesSync
     */
    protected $categoriesSync;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var PimCategory
     */
    protected $pimCategory;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var Processor
     */
    protected $imageProcessor;

    /**
     * @var Factory
     */
    protected $optionsFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ProductAttachmentFactory
     */
    protected $productAttachmentFactory;

    /**
     * @var AttachmentCollectionFactory
     */
    protected $attachmentCollectionFactory;

    /**
     * @var MultistoreHelper
     */
    protected $multistoreHelper;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ProductFactory $productFactory,
        DataParser $dataParser,
        DataFormatter $dataFormatter,
        AttributeMapper $attributeMapper,
        Logger $logger,
        CategoriesSync $categoriesSync,
        CategorySync $categorySync,
        PimCategory $pimCategory,
        Config $config,
        ProductRepositoryInterface $productRepository,
        AttributeCollectionFactory $attributeCollectionFactory,
        AttributeOptionManagementInterface $attributeOptionManagement,
        AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        AttributeOptionInterfaceFactory $optionFactory,
        AttributeSourceTableFactory $attributeSourceTableFactory,
        ProductAttributeRepositoryInterface $attributeRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        DirectoryList $directoryList,
        File $file,
        Processor $imageProcessor,
        Factory $optionsFactory,
        ProductAttachmentFactory $productAttachmentFactory,
        AttachmentCollectionFactory $attachmentCollectionFactory,
        MultistoreHelper $multistoreHelper,
        EventManager $eventManager,
        FilterManager $filterManager
    ) {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->dataFormatter = $dataFormatter;
        $this->attributeMapper = $attributeMapper;
        $this->optionLabelFactory = $optionLabelFactory;
        $this->optionFactory = $optionFactory;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->attributeSourceTableFactory = $attributeSourceTableFactory;
        $this->attributeRepository = $attributeRepository;
        $this->dataParser = $dataParser;
        $this->categoriesSync = $categoriesSync;
        $this->categorySync = $categorySync;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->pimCategory = $pimCategory;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->imageProcessor = $imageProcessor;
        $this->optionsFactory = $optionsFactory;
        $this->config = $config;
        $this->productAttachmentFactory = $productAttachmentFactory;
        $this->attachmentCollectionFactory = $attachmentCollectionFactory;
        $this->multistoreHelper = $multistoreHelper;
        $this->eventManager = $eventManager;
        $this->filterManager = $filterManager;
    }

    /**
     * @param $productData
     */
    public function processProduct($productData, $type, $variants = [], $ignoreAssetsUpdate = false)
    {
        $sku = $productData[$this->attributeMapper->getPimAttributeCode('sku')];

        if ($type == 'variable') {

            $parentSku = "";
            $sku = $this->dataParser->parseProductGroupSku($productData);

            /**
             * Don't trust product's group sku in PIM since it can change there.
             * Instead, get first child's sku in PIM, find the correspnding magento product
             * and look up parent's sku by group_sku attribute
             */
            foreach($variants as $variant){
                $childSku = $variant[$this->attributeMapper->getPimAttributeCode('sku')];
                try {
                    $childMagentoProduct = $this->productRepository->get($childSku);
                    if ($childMagentoProduct->getId()) {
                        $parentSku = $childMagentoProduct->getData('catsy_product_group_sku');
                        break;
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    // do nothing
                }
            }

            /**
             * If group sku changed in PIM, update it in Magento
             */
            if($parentSku && $sku != $parentSku){
                $this->logger->info("Configurable product group sku in PIM [{$sku}] doesn't match parent's sku [$parentSku] previously saved in Magento");

                // Update Magento's parent sku to a new value coming from PIM
                $parentMagentoProduct = $this->productRepository->get($parentSku);
                $parentMagentoProduct->setSku($sku);
                $parentMagentoProduct->save();
            }

            if (empty($sku)) {
                $this->logger->error("Configurable product [{$productData[$this->attributeMapper->getPimAttributeCode('sku')]}] doesn't have group sku defined");
                return;
            }
        }

        $data = [];

        // update product
        try {

            $product = $this->productRepository->get($sku);
            $this->logger->info("Updating product [{$sku}]...");

            /**
             * Log and skip products with type changed in PIM.
             * This will also catch instanses when a group sku used in PIM already used as a sku for a simple product.
             */
            if (
                ($product->getTypeId() == 'configurable' && $type != 'variable') ||
                ($product->getTypeId() == 'simple' && $type == 'variable')
            ){
                $this->logger->critical("Error updating product [{$sku}] - product type changed");
                return;
            }

            // create product
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {

            $this->logger->info("Creating product [{$sku}]...");
            $product = $this->productFactory->create();
            $product->setAttributeSetId($product->getDefaultAttributeSetId());
        }

        try {
            if ($type == 'simple') { // simple product
                $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
                $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
            } elseif ($type == 'variant') { // child product
                $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE);
                $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
            } elseif ($type == 'variable') { // configurable product

                if (!$this->dataParser->parseOptionAttributeKeys($productData)) {
                    $this->logger->error("Configurable product is missing product options");
                    return;
                }
                $product->setTypeId('configurable');
                $product->setVisibility(Visibility::VISIBILITY_BOTH);
                $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
                $product->setStockData(['use_config_manage_stock'=> 0, 'manage_stock'=> 0, 'backorders' => 1, 'use_config_backorders' => 0]);

                $this->handleProductChildren($product, $productData, $variants);
            } else {
                $this->logger->error("Unsupported product type [{$type}]");
                return;
            }

            $magentoAttributes = $this->getMagentoAttributes();

            // build data array
            foreach ($magentoAttributes as $magentoAttribute) {
                $magentoAttributeCode = $magentoAttribute->getAttributeCode();
                $pimAttributeCode = $this->attributeMapper->getPimAttributeCode($magentoAttributeCode);

                if ($pimAttributeCode && key_exists($pimAttributeCode, $productData)) {
                    $data[$magentoAttributeCode] = $this->dataFormatter->format($magentoAttributeCode, $this->dataParser->parseAttributeData($productData, $pimAttributeCode));

                    if ($magentoAttribute->getFrontendInput() == 'select' && $magentoAttribute->getBackendType() != 'int') {
                        $optionValue = $this->createOrGetAttributeOptions($magentoAttributeCode, [$productData[$pimAttributeCode]]);
                        $data[$magentoAttributeCode] = $optionValue ? current($optionValue) : null;
                    } elseif ($magentoAttribute->getFrontendInput() == 'boolean') {
                        $data[$magentoAttributeCode] = ($productData[$pimAttributeCode] == 'Y' || $productData[$pimAttributeCode] == 1 || $productData[$pimAttributeCode] === true || strtolower($productData[$pimAttributeCode]) == "yes");
                    }
                }
            }

            // Update product
            foreach ($data as $attribute => $value) {
                $product->setData($attribute, $value);
            }
            $product->setData('sku', $sku);

            // Set category
            $assignedCategories = [$this->categorySync->getMagentoAllCategoryId()];
            $pimCategoryNames = $this->dataParser->parseProductCategoryNames($productData);
            foreach ($pimCategoryNames as $pimCategoryName) {
                $magentoCategoryId = $this->getMagentoCategoryIdByName($pimCategoryName);
                if ($magentoCategoryId) {
                    $assignedCategories[] = $magentoCategoryId;
                }
            }
            if (!$pimCategoryNames) {
                $this->logger->error("Product [{$sku}] doesn't have assigned category");
            }

            $product->setCategoryIds($assignedCategories);
            $product->setWebsiteIds([$this->multistoreHelper->getRetailWebsiteId()]);

            // Save product
            try {
                if ($type == 'variable') {
                    $productName = $this->dataParser->parseProductGroupName($productData) ?: $this->dataParser->parseProductName($productData);
                    $product->setName($productName);
                }

                /**
                 * If child product name doesn't contain the sku, append it
                 * It helps to resolve duplicate URL issue during the firebear product import
                 */
                if ($type == 'variant' && stripos($this->dataParser->parseProductName($productData), $sku) === false) {
                    $newChildProductName = $this->dataParser->parseProductName($productData) . ' (' . $sku . ')';
                    $product->setName($newChildProductName);
                }

                if(!$product->getName()){
                    $this->logger->error("Product [{$sku}] doesn't have name");
                    return;
                }

                $lastPimUpdatedDate = $this->dataParser->parseUpdatedDate($productData);
                if ($lastPimUpdatedDate <= $product->getPimProductUpdatedAt() && !$this->scopeConfig->getValue('pim/sync/force_update')) {
                    $this->logger->info("Nothing to update");
                } else {
                    $product->setPimProductUpdatedAt($this->dataParser->parseUpdatedDate($productData));
                    $product->save();
                    $this->eventManager->dispatch('pim_product_update_after', ['product' => $product]);
                }
            } catch (\Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException $e) {
                $this->logger->error("URL already exists for product [".$product->getSku()."]");

                //Set url_key attribute for all products based on name from PIM and product's sku
                $urlKey = $this->filterManager->translitUrl($product->getName().'-'.$product->getSku());

                $this->logger->info("Setting URL key and path to [{$urlKey}]");
                $product->setUrlKey($urlKey);
                $product->setUrlPath($urlKey);
                $product->save();

                $this->eventManager->dispatch('pim_product_update_after', ['product' => $product]);
            }

            if (!$ignoreAssetsUpdate) {
                if ($this->scopeConfig->getValue('pim/sync/enable_images_sync')) {
                    $this->processImages($product, $productData);
                }
                if ($this->scopeConfig->getValue('pim/sync/enable_pdf_sync')) {
                    $this->processAttachments($product, $productData);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error updating product [{$sku}] - " . $e->getMessage());
        }

        return $product;
    }


    /**
     * @return mixed
     */
    protected function getMagentoAttributes()
    {
        if (!$this->magentoAttributes) {
            $this->magentoAttributes = $this->attributeCollectionFactory->create()->getItems();
        }
        return $this->magentoAttributes;
    }

    /**
     * @param $product
     * @param $productData
     * @param $variants
     */
    protected function handleProductChildren($product, $productData, $variants)
    {
        try {
            $magentoAttributeIds = [];
            $magentoAttributes = [];
            $optionAttributeKeys = $this->dataParser->parseOptionAttributeKeys($productData);

            foreach ($optionAttributeKeys as $optionAttributeKey) {
                $magentoAttributeCode = $this->scopeConfig->getValue('pim/sync/magento_attribute_prefix') . $optionAttributeKey;
                $attribute = $product->getResource()->getAttribute($magentoAttributeCode);
                if ($attribute) {
                    $magentoAttributes[] = $attribute;
                    $magentoAttributeIds[] = $attribute->getId();
                } else {
                    $this->logger->error("Check if attribute [{$magentoAttributeCode}] exists");
                }
            }

            $parentAssetUrls = [];
            $associatedProductIds = [];
            $configurableProductsData = [];
            $product->getTypeInstance()->setUsedProductAttributeIds($magentoAttributeIds, $product);
            $configurableAttributesData = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
            $product->setCanSaveConfigurableAttributes(true);
            $product->setConfigurableAttributesData($configurableAttributesData);

            // Keep track of parent assets to avoid images duplicates when adding child images
            $assetMatchesFound = 0;
            $parentAssets = $this->dataParser->parseProductAssets($productData);
            foreach ($parentAssets as $parentAsset) {
                $parentAssetUrls[] = $this->dataParser->parseAssetOriginalUrl($parentAsset);
            }

            // create/update children
            foreach ($variants as $variant) {
                $productAttributeData = [];

                // if all child images are macthing parent images, don't load them
                $ignoreAssetsUpdate = false;
                $assetMatchesFound = 0;
                $variantAssets = $this->dataParser->parseProductAssets($variant);
                foreach ($variantAssets as $variantAsset) {
                    if (in_array($this->dataParser->parseAssetOriginalUrl($variantAsset), $parentAssetUrls)) {
                        $assetMatchesFound++;
                    }
                }
                if ($assetMatchesFound == count($variantAssets)) {
                    $ignoreAssetsUpdate = true;
                }

                // create or update child product
                $childProduct = $this->processProduct($variant, 'variant', [], $ignoreAssetsUpdate);

                $associatedProductIds[] = $childProduct->getId();
                $configurableProductOptions = [];
                foreach ($magentoAttributes as $magentoAttribute) {
                    try {
                        $isOptionValueFound = false;
                        $pimAttributeCode = $this->dataParser->getAttributeCode($magentoAttribute->getAttributeCode());
                        if (!empty($variant[$pimAttributeCode])) {
                            $variantAttributeValue = $variant[$pimAttributeCode];
                            $options = $magentoAttribute->getOptions();
                            foreach ($options as $option) {
                                if ($option->getLabel() == $variantAttributeValue) {
                                    $isOptionValueFound = true;
                                    break;
                                }
                            }
                        } else {
                            $this->logger->error("Error assigning child to parent [{$product->getSku()}] - missing configurable attribute value for [{$pimAttributeCode}]");
                        }

                        if ($isOptionValueFound) {
                            $productAttributeData[] = [
                                'label' => $variantAttributeValue,
                                'attribute_id' => $magentoAttribute->getId(),
                                'value_index' => $option->getValue(),
                                'is_percent' => 0,
                                'pricing_value' => '0'
                            ];

                            $attributeValues = [];
                            foreach ($options as $option) {
                                $attributeValues[] = [
                                    'label' => $magentoAttribute->getStoreLabel(),
                                    'attribute_id' => $magentoAttribute->getId(),
                                    'value_index' => $option->getValue(),
                                ];
                            }

                            $configurableProductOptions[] =
                                [
                                    'attribute_id' => $magentoAttribute->getId(),
                                    'code' => $magentoAttribute->getAttributeCode(),
                                    'label' => $magentoAttribute->getStoreLabel(),
                                    'position' => '0',
                                    'values' => $attributeValues,
                                ];
                        }
                    } catch (\Exception $e) {
                        $this->logger->error("Error assigning child to parent [{$product->getSku()}] - " . $e->getMessage());
                    }
                }
                $configurableProductsData[$childProduct->getId()] = $productAttributeData;
            }

            $product->setCanSaveConfigurableAttributes(true);
            $product->setAssociatedProductIds($associatedProductIds);
            $product->setConfigurableProductsData($configurableProductsData);

            $configurableOptions = $this->optionsFactory->create($configurableProductOptions);
            $extensionConfigurableAttributes = $product->getExtensionAttributes();
            $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);

            $this->logger->info("Children assigned to parent [{$product->getSku()}]");
        } catch (\Exception $e) {
            $this->logger->error("Error handling children assignment - " . $e->getMessage());
        }
    }

    public function getPimProductImages($productData)
    {
        $assets = $this->dataParser->parseProductAssets($productData);
        $images = [];
        foreach ($assets as $asset) {
            if ($this->dataParser->isAssetImage($asset)) {
                $images[] = $asset;
            }
        }
        return $images;
    }

    public function getPimProductPdfs($productData)
    {
        $assets = $this->dataParser->parseProductAssets($productData);
        $pdfs = [];
        foreach ($assets as $asset) {
            if ($this->dataParser->isAssetPdf($asset)) {
                $pdfs[] = $asset;
            }
        }
        return $pdfs;
    }

    /**
     * @param $product
     * @param $productData
     */
    public function processImages($product, $productData)
    {
        try {
            $this->logger->info("Updating images...");

            $assets = $this->getPimProductImages($productData);
            if (!$assets) {
                $this->logger->info("No images to update");
                return;
            }

            $lastUpdatedImage = max(array_column($assets, 'update_date'));
            if ($lastUpdatedImage <= $product->getPimImagesUpdatedAt()) {
                $this->logger->info("No images to update");
                return;
            }

            // Remove existing images to avoid duplicates
            $productImages = $product->getMediaGalleryImages();
            foreach ($productImages as $image) {
                $this->imageProcessor->removeImage($product, $image->getFile());
            }
            if (count($productImages)) {
                $product->save();
                $product->setMediaGalleryEntries([]);
            }

            // Add images
            $addedAssets = [];
            foreach ($assets as $asset) {
                try {
                    $addedAsset = $this->addPimImage($asset, $product, $productData, $addedAssets);
                    $addedAssets[] = $addedAsset;
                } catch (\Exception $e) {
                    $this->logger->error("Error updating image for [{$product->getSku()}] - " . $e->getMessage());
                }
            }
            $product->setPimImagesUpdatedAt($lastUpdatedImage);
            $product->save();
        } catch (\Exception $e) {
            $this->logger->error("Error updating images [{$product->getSku()}] - " . $e->getMessage());
        }
    }

    public function addPimImage($asset, $product, $productData, $addedAssets)
    {
        if ($this->dataParser->parseMainImage($productData) == $this->dataParser->parseAssetId($asset)) {
            $flags = ['image', 'small_image', 'thumbnail'];
        } else {
            $flags = [];
        }

        $tmpDir = $this->getMediaDirTmpDir();
        $this->file->checkAndCreateFolder($tmpDir);
        $imageUrl = $this->dataParser->parseAssetOriginalUrl($asset);
        if (!in_array($imageUrl, $addedAssets)) {
            $newFileName = $tmpDir . '/' . baseName($imageUrl);
            $this->file->read($imageUrl, $newFileName);
            // TODO: Test passing true as third argument to make method move the file so tmp doesn't fill up
            $product->addImageToMediaGallery($newFileName, $flags, false, false);
        }

        return $imageUrl;
    }

    /**
     * @param $product
     * @param $productData
     */
    public function processAttachments($product, $productData)
    {
        try {
            $this->logger->info("Updating attachments...");

            $assets = $this->getPimProductPdfs($productData);
            if (!$assets) {
                $this->logger->info("No attachments to update");
                return;
            }

            $lastUpdatedPdf = max(array_column($assets, 'update_date'));
            if ($lastUpdatedPdf <= $product->getPimPdfsUpdatedAt()) {
                $this->logger->info("No attachments to update");
                return;
            }

            // Remove attachments first to avoid duplicates
            $attachments = $this->attachmentCollectionFactory->create()
                ->addFieldToFilter('is_active', ['eq' => \Sparsh\ProductAttachment\Model\ProductAttachment::STATUS_ENABLED]);
            $attachments->getSelect()->joinLeft(
                ['attach_products' => 'sparsh_product_attachment'],
                'main_table.attachment_id = attach_products.attachment_id',
                ['attach_products.product_id']
            );
            $attachments->addFieldToFilter('product_id', ['eq' => $product->getId()]);
            foreach ($attachments as $attachment) {
                $attachment->delete();
            }

            $addedPdfs = [];
            $this->logger->info("Updating PDF attachments...");
            foreach ($assets as $asset) {
                if (!in_array($this->dataParser->parseAssetId($asset), $addedPdfs)) {
                    $assetUrl = $this->dataParser->parsePdfUrl($asset);
                    $baseFileName = baseName($assetUrl);

                    $baseDir = $this->directoryList->getPath(DirectoryList::MEDIA) . self::ATTACHMENTS_DIRECTORY;
                    $this->file->checkAndCreateFolder($baseDir);
                    $fullBaseFileName = $baseDir . '/' . $baseFileName;

                    // Avoid duplicate filenames
                    if ($this->file->fileExists($fullBaseFileName, true)) {
                        $filename = $this->getUniqueName(pathinfo($fullBaseFileName, PATHINFO_FILENAME));
                        $baseFileName = $filename . '.' . pathinfo($fullBaseFileName, PATHINFO_EXTENSION);
                    }
                    $fullBaseFileName = $baseDir . '/' . $baseFileName;

                    // Save file
                    $result = $this->file->read($assetUrl, $fullBaseFileName);
                    $title = str_replace('+', ' ', $baseFileName);
                    $title = str_replace('.pdf', '', $title);

                    if ($result) {
                        $addedPdfs[] = $this->dataParser->parseAssetId($asset);
                        // Use Sparsh extension to handle product attachments
                        $attachment = $this->productAttachmentFactory->create();
                        $data['title'] = $title;
                        $data['is_active'] = "1";
                        $data['attach_file'] = $baseFileName;
                        $data['attachment_products'] = json_encode([$product->getId()=>true]);
                        $attachment->setData($data);
                        $attachment->save();
                    }
                }
            }

            $product->setPimPdfsUpdatedAt($lastUpdatedPdf);
            $product->save();
        } catch (\Exception $e) {
            $this->logger->error("Error updating PDF attachment [{$product->getSku()}] - " . $e->getMessage());
        }
    }

    /**
     * @param $filename
     * @return string
     */
    public function getUniqueName($filename)
    {
        return $filename . '-' . time();
    }

    /**
     * @return string
     */
    public function getMediaDirTmpDir()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'tmp';
    }

    /**
     * @param $name
     * @return int|void
     */
    protected function getMagentoCategoryIdByName($name)
    {
        // fall back to default category
        $magentoCategoryId = $this->categorySync->getDefaultCategoryId();

        // get magento category by name
        $magentoCategory = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToFilter('name', $name)
            ->getFirstItem();

        // check if exists
        if ($magentoCategory->getId()) {
            $magentoCategoryId = $magentoCategory->getId();

        // create new using pim category name
        } elseif ($name) {
            $pimCategory = $this->pimCategory->getByName($name);
            $pimCategoryId = $this->pimCategory->getId($pimCategory);
            if (!in_array($pimCategoryId, $this->config->getPimCategoryIdsToIgnore())) {
                $magentoCategoryId = $this->categoriesSync->run([$pimCategory]);
            }
        }

        return $magentoCategoryId;
    }

    /**
     * @param $attributeCode
     * @param $attributeValues
     * @return array
     */
    private function createOrGetAttributeOptions($attributeCode, $attributeValues)
    {
        $ids = [];
        $attributeValues = array_map('trim', $attributeValues);

        try {
            foreach ($attributeValues as $value) {
                if ($value) {
                    $ids[] = $this->createOrGetId($attributeCode, $value);
                }
            }
        } catch (\Exception $e) {
            $msg = "Error updating product attribute options: " . $e->getMessage();
            $this->logger->error($msg);
        }

        return $ids;
    }

    /**
     *
     * Taken from: https://magento.stackexchange.com/a/103951/567
     *
     * Find or create a matching attribute option
     *
     * @param string $attributeCode Attribute the option should exist in
     * @param string $label Label to find or add
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOrGetId($attributeCode, $label)
    {
        if (strlen($label) < 1) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Label for %1 must not be empty.', $attributeCode)
            );
        }

        // Does it already exist?
        $optionId = $this->getOptionId($attributeCode, strtoupper($label));

        if (!$optionId) {
            // If no, add it.

            $optionLabel = $this->optionLabelFactory->create();
            $optionLabel->setStoreId(0);
            $optionLabel->setLabel(strtoupper($label));

            $option = $this->optionFactory->create();
            $option->setLabel(strtoupper($label));
            $option->setStoreLabels([$optionLabel]);
            $option->setSortOrder(0);
            $option->setIsDefault(false);

            $this->attributeOptionManagement->add(
                Product::ENTITY,
                $this->getAttribute($attributeCode)->getAttributeId(),
                $option
            );

            // Get the inserted ID. Should be returned from the installer, but it isn't.
            $optionId = $this->getOptionId($attributeCode, strtoupper($label), true);
        }

        return $optionId;
    }

    /**
     * Taken from: https://magento.stackexchange.com/a/103951/567
     *
     * Find the ID of an option matching $label, if any.
     *
     * @param string $attributeCode Attribute code
     * @param string $label Label to find
     * @param bool $force If true, will fetch the options even if they're already cached.
     * @return int|false
     */
    public function getOptionId($attributeCode, $label, $force = false)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
        $attribute = $this->getAttribute($attributeCode);

        // Build option array if necessary
        if ($force === true || !isset($this->attributeValues[$attribute->getAttributeId()])) {
            $this->attributeValues[$attribute->getAttributeId()] = [];

            // We have to generate a new sourceModel instance each time through to prevent it from
            // referencing its _options cache. No other way to get it to pick up newly-added values.

            $sourceModel = $this->attributeSourceTableFactory->create();
            $sourceModel->setAttribute($attribute);

            foreach ($sourceModel->getAllOptions() as $option) {
                $this->attributeValues[$attribute->getAttributeId()][strtoupper($option['label'])] = $option['value'];
            }
        }

        // Return option ID if exists
        if (isset($this->attributeValues[$attribute->getAttributeId()][strtoupper($label)])) {
            return $this->attributeValues[$attribute->getAttributeId()][strtoupper($label)];
        }

        // Return false if does not exist
        return false;
    }

    /**
     * @param $attributeCode
     * @return mixed
     */
    public function getAttribute($attributeCode)
    {
        return $this->attributeRepository->get($attributeCode);
    }
}
