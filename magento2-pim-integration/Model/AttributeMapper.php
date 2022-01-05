<?php
namespace Ripen\PimIntegration\Model;

abstract class AttributeMapper
{
    protected $attributeMapping;

    /**
     * @var \Ripen\PimIntegration\Model\Config
     */
    protected $config;

    /**
     * AttributeMapper constructor.
     * @param \Ripen\PimIntegration\Model\Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param $magentoAttributeCode
     * @return string|string[]|null
     */
    public function getPimAttributeCode($magentoAttributeCode)
    {
        $pimAttributeCode = null;
        $attributeMapping = $this->getAttributeMapping();

        if (in_array($magentoAttributeCode, array_keys($attributeMapping))) {
            $pimAttributeCode = $attributeMapping[$magentoAttributeCode];
        } elseif (strpos($magentoAttributeCode, $this->config->getAttributePrefix()) === 0) {
            $pimAttributeCode =  str_replace($this->config->getAttributePrefix(), '', $magentoAttributeCode);
        }
        return $pimAttributeCode;
    }

    protected function getAttributeMapping()
    {
        return $this->attributeMapping;
    }

    /**
     * @param $value
     */
    protected function setAttributeMapping($value)
    {
        $this->attributeMapping = $value;
    }
}
