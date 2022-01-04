<?php
namespace Ripen\CatsyIntegration\Model;

class AttributeMapper extends \Ripen\PimIntegration\Model\AttributeMapper
{
    const ATTRIBUTES_MAPPING = [
        'sku' => 'item_id',
        'name' => 'product_name',
        'short_description' => 'product_introduction_statement',
        'weight' => 'weight',
        'url_key' => 'url_slug',
    ];

    /**
     * @var
     */
    protected $attributeMapping;

    /**
     * AttributeMapper constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->setAttributeMapping(self::ATTRIBUTES_MAPPING);
        parent::__construct($config);
    }
}
