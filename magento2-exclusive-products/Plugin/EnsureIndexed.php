<?php
namespace Ripen\ExclusiveProducts\Plugin;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\AttributeData;
use Ripen\ExclusiveProducts\Model\Product;

class EnsureIndexed
{
    /**
     * @param AttributeData $attributeData
     * @param AttributeCollection $attributeCollection
     * @return AttributeCollection
     */
    public function afterAddIndexedFilterToAttributeCollection(
        AttributeData $attributeData,
        AttributeCollection $attributeCollection
    ) {
        $attributeCollection->getSelect()->orWhere("attribute_code IN (?)", Product::PRODUCT_RESTRICTION_FIELDS);
        return $attributeCollection;
    }
}
