<?php
namespace Ripen\PimIntegration\Model\Sync;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Ripen\PimIntegration\Logger\Logger;
use Ripen\PimIntegration\Model\DataParserInterface as DataParser;

class AttributesSync
{
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        AttributeSync $attributeSync,
        DataParser $dataParser,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->attributeSync = $attributeSync;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->dataParser = $dataParser;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * @param $attributes
     */
    public function run($attributes)
    {
        $this->logger->info('Syncing attributes...');
        if (!$this->scopeConfig->getValue('pim/sync/enable_attribute_sync')) {
            $this->logger->info('Interrupted. Attribute sync is disabled in admin settings.');
            return;
        }

        $i = 0;
        $pimAttributeCodes = [];
        foreach ($attributes as $attribute) {
            $i++;
            $attributeCode = $this->dataParser->parseAttributeCode($attribute);

            try {
                $result = $this->attributeSync->processAttribute($attribute);
                $pimAttributeCodes[] = $this->getAttributePrefix() . $attributeCode;
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                $this->logger->error("Failed tp update product [{$i}][{$attributeCode}] - " . $e->getMessage());
            }
        }
        $this->logger->info('Syncing attributes completed');

        try {
            $this->logger->info('Sanitizing Magento attributes...');
            $magentoAttributes = $this->attributeCollectionFactory->create()->getItems();

            foreach ($magentoAttributes as $attribute) {
                if (!in_array($attribute->getAttributeCode(), $pimAttributeCodes) && strpos($attribute->getAttributeCode(), $this->getAttributePrefix()) === 0) {
                    $this->logger->info("Remove attribute [{$attribute->getAttributeCode()}]");
                    $attribute->delete();
                }
            }
            $this->logger->info('Sanitizing attributes completed');
        } catch (\Exception $e) {
            $this->logger->error("Error - " . $e->getMessage());
        }
    }

    protected function getAttributePrefix()
    {
        return $this->scopeConfig->getValue('pim/sync/magento_attribute_prefix');
    }
}
