<?php

namespace Ripen\PromoRuleSku\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class UpgradeData
 * @package Ripen\PromoRuleSku\Setup
 */
class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $currentVersion = $context->getVersion();

        if (!$currentVersion){
            $this->setRuleSkuColumn($setup);
        }

        $setup->endSetup();
    }

    protected function setRuleSkuColumn(ModuleDataSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('catalogrule'),
            'rule_sku',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Rule Sku'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('salesrule'),
            'rule_sku',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Rule Sku'
            ]
        );

    }
}
