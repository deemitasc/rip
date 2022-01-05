<?php
namespace Ripen\PimIntegration\Model;

abstract class DataFormatter
{
    protected $fieldsRequiredFormatting;

    /**
     * DataFormatter constructor.
     */
    public function __construct()
    {
        $this->setFieldsRequiredFormatting(static::FIELDS_REQUIRED_FORMATTING);
    }

    /**
     * @param $magentoAttributeCode
     * @param $rawValue
     * @return mixed
     */
    public function format($magentoAttributeCode, $rawValue)
    {
        $formattedValue = $rawValue;
        $fieldsRequiredFormatting = $this->getFieldsRequiredFormatting();

        if (in_array($magentoAttributeCode, array_keys($fieldsRequiredFormatting)) && $rawValue) {
            $action = $fieldsRequiredFormatting[$magentoAttributeCode];
            if (is_callable([$this, $action])) {
                $formattedValue = $this->$action($rawValue);
            }
        }

        return $formattedValue;
    }

    /**
     * @return mixed
     */
    protected function getFieldsRequiredFormatting()
    {
        return $this->fieldsRequiredFormatting;
    }

    /**
     * @param $value
     */
    protected function setFieldsRequiredFormatting($value)
    {
        $this->fieldsRequiredFormatting = $value;
    }
}
