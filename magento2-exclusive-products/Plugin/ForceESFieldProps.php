<?php
namespace Ripen\ExclusiveProducts\Plugin;

use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Ripen\ExclusiveProducts\Model\Product;

class ForceESFieldProps
{
    /**
     * Workaround for this issue that prevents this from being defined in XML:
     * https://github.com/Smile-SA/elasticsuite/issues/2085
     *
     * @param Field $field
     * @param array $propertyConfig
     * @return array
     */
    public function afterGetMappingPropertyConfig(
        Field $field,
        array $propertyConfig
    ) {
        if (in_array($field->getName(), Product::PRODUCT_RESTRICTION_FIELDS)) {
            $propertyConfig['type'] = Field::FIELD_TYPE_TEXT;
            $propertyConfig['analyzer'] = Field::ANALYZER_WHITESPACE;
            unset($propertyConfig['ignore_above']);
        }

        return $propertyConfig;
    }
}
