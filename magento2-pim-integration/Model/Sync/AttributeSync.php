<?php
namespace Ripen\PimIntegration\Model\Sync;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Ripen\PimIntegration\Logger\Logger;
use Ripen\PimIntegration\Model\DataParserInterface as DataParser;

class AttributeSync
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var DataParser
     */
    protected $dataParser;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DataParser $dataParser,
        Logger $logger,
        FilterBuilder $filterBuilder,
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->filterBuilder = $filterBuilder;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeRepository = $attributeRepository;
        $this->dataParser = $dataParser;
    }

    /**
     * @param $pimAttribute
     * @return bool
     */
    public function processAttribute($pimAttribute)
    {
        $attributeCode = $this->scopeConfig->getValue('pim/sync/magento_attribute_prefix') . $this->dataParser->parseAttributeCode($pimAttribute);
        $attributeType = $this->dataParser->parseAttributeType($pimAttribute);

        try {
            $attribute = $this->attributeRepository->get('catalog_product', $attributeCode);
            $this->logger->info("Updating attribute [{$attributeCode}]");

            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $attributeFieldsToSkip = array_map('trim', explode(
                ',',
                (string) $this->scopeConfig->getValue('pim/sync/pim_ignored_attribute_properties')
            ));
            $attributeFields = [
                'frontend_label' => $this->dataParser->parseAttributeName($pimAttribute),
                'is_required' => false,
                'is_searchable' => $this->dataParser->parseAttributeIsSearchable($pimAttribute),
                'is_filterable' => $this->dataParser->parseAttributeIsFilterable($pimAttribute),
                'is_filterable_in_search' => $this->dataParser->parseAttributeIsFilterable($pimAttribute),
                'is_global' => true,
                'facet_min_coverage_rate' => 5,
            ];

            foreach ($attributeFieldsToSkip as $field) {
                unset($attributeFields[$field]);
            }

            foreach ($attributeFields as $attributeField => $pimAttributeValue) {
                if ($attribute->getData($attributeField) != $pimAttributeValue) {
                    $eavSetup->updateAttribute('catalog_product', $attributeCode, $attributeField, $pimAttributeValue);
                }
            }

            /**
             * Update attribute options for selectable attributes
             */
            $pimSelectLabels = [];
            $magentoSelectLabels = [];

            $pimSelectLabels = array_column($this->dataParser->parseAttributeSelectValues($pimAttribute), 'value');
            $options = $attribute->getOptions();
            foreach ($options as $option) {
                $label = gettype($option->getLabel()) == "object" ? $option->getLabel()->getText() : $option->getLabel();
                $magentoSelectLabels[$label] = $option->getValue();
            }

            if ($pimSelectLabels && $magentoSelectLabels) {
                foreach ($pimSelectLabels as $pimSelectLabel) {
                    if (in_array($pimSelectLabel, array_keys($magentoSelectLabels))) {
                        // Skip if pim attribute already exists
                        continue;
                    } else {
                        // Add new pim attribute
                        $option = [];
                        $option['attribute_id'] = $attribute->getAttributeId();
                        $option['value'][$pimSelectLabel][0] = $pimSelectLabel;
                        $eavSetup->addAttributeOption($option);
                    }
                }

                /**
                 * Remove attribute options that are not in pim
                 */
                $optionsToRemove = [];
                foreach ($magentoSelectLabels as $magentoLabel=>$magentoValue) {
                    if (!in_array($magentoLabel, $pimSelectLabels)) {
                        $optionsToRemove['delete'][$magentoValue] = true;
                        $optionsToRemove['value'][$magentoValue] = true;
                    }
                }
                $eavSetup->addAttributeOption($optionsToRemove);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->info("Creating new attribute [{$attributeCode}]");

            if (in_array($attributeType, ['STRING','CATEGORY'])) {
                $this->createTextAttribute($attributeCode, $pimAttribute);
            } elseif ($attributeType == 'PICKLIST') {
                $this->createSelectAttribute($attributeCode, $pimAttribute);
            } elseif ($attributeType == 'ASSET') {
                //skip for now
            } else {
                $this->logger->error("Failed processing attribute [{$attributeCode}] - unrecognized type [{$attributeType}]");
            }
        } catch (\Exception $e) {
            $this->logger->error("Failed processing attribute [{$attributeCode}]: " . $e->getMessage());
        }

        return true;
    }

    /**
     * @param $attributeCode
     * @param $attributeData
     * @param $type
     * @param $input
     * @param $source
     * @param null $values
     */
    protected function createAttribute($attributeCode, $attributeData, $type, $input, $source, $values = null)
    {
        try {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $isSearchable = $this->dataParser->parseAttributeIsSearchable($attributeData);
            $isFilterable = $this->dataParser->parseAttributeIsFilterable($attributeData);
            $isVisibleOnFront = $this->dataParser->parseAttributeIsVisibleOnFront($attributeData);

            $attributeData = [
                'type' => $type,
                'input' => $input,
                'label' => $this->dataParser->parseAttributeName($attributeData),
                'group' => 'PIM Attributes',
                'used_in_product_listing' => false,
                'user_defined' => true,
                'unique' => false,
                'required' => false,
                'backend' => '',
                'source' => $source,
                'searchable' => $isSearchable,
                'visible_on_front' => $isVisibleOnFront,
                'filterable' => $isFilterable,
                'filterable_in_search' => $isFilterable,
                'facet_min_coverage_rate' => 5,
                'position' => 100
            ];

            if ($values) {
                $attributeData['option'] = ['values' => $values];
            }

            $eavSetup->addAttribute('catalog_product', $attributeCode, $attributeData);
        } catch (\Exception $e) {
            $this->logger->error("Attribute fails to create: " . $e->getMessage());
        }
    }

    /**
     * @param $attributeCode
     * @param $attributeData
     */
    protected function createTextAttribute($attributeCode, $attributeData)
    {
        $this->createAttribute($attributeCode, $attributeData, 'varchar', 'text', '');
    }

    /**
     * @param $attributeCode
     * @param $attributeData
     */
    protected function createSelectAttribute($attributeCode, $attributeData)
    {
        $values = array_column($this->dataParser->parseAttributeSelectValues($attributeData), 'value');
        if (in_array('Yes', $values) && in_array('No', $values) && count($values) == 2) {
            $values = ['', 'Yes', 'No'];
        }

        $source = "Magento\Eav\Model\Entity\Attribute\Source\Table";
        $type = 'varchar';
        $input = 'select';

        $this->createAttribute($attributeCode, $attributeData, $type, $input, $source, $values);
    }
}
