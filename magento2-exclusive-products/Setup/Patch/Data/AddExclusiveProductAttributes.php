<?php

namespace Ripen\ExclusiveProducts\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddExclusiveProductAttributes implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var EavSetupFactory */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute('catalog_product', 'exclusive_to', [
            'type' => 'text',
            'input' => 'textarea',
            'label' => 'Product Exclusive To',
            'group' => 'Product Exclusivity Attributes',
            'used_in_product_listing' => false,
            'user_defined' => true,
            'unique' => false,
            'required' => false,
            'backend' => '',
            'source' => '',
            'searchable' => false,
            'visible_on_front' => false,
            'filterable' => false,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => true,
            'is_filterable_in_search' => false
        ]);

        $eavSetup->addAttribute('catalog_product', 'blocked_for', [
            'type' => 'text',
            'input' => 'textarea',
            'label' => 'Product Blocked For',
            'group' => 'Product Exclusivity Attributes',
            'note' => __('Space separated. Note: this setting will only be processed for customers whose restriction mode is set at "Block Mode"'),
            'used_in_product_listing' => false,
            'user_defined' => true,
            'unique' => false,
            'required' => false,
            'backend' => '',
            'source' => '',
            'searchable' => false,
            'visible_on_front' => false,
            'filterable' => false,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => true,
            'is_filterable_in_search' => false
        ]);

        $eavSetup->addAttribute('catalog_product', 'allowed_for', [
            'type' => 'text',
            'input' => 'textarea',
            'label' => 'Product Allowed For',
            'group' => 'Product Exclusivity Attributes',
            'note' => __('Space separated. Note: this setting will only be processed for customers whose restriction mode is set at "Allow Mode"'),
            'used_in_product_listing' => false,
            'user_defined' => true,
            'unique' => false,
            'required' => false,
            'backend' => '',
            'source' => '',
            'searchable' => false,
            'visible_on_front' => false,
            'filterable' => false,
            'is_used_in_grid' => false,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_filterable_in_search' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
