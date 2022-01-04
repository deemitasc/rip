<?php


namespace Ripen\ExclusiveProducts\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class CustomerRestrictionModes extends AbstractSource
{
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Normal')],
            ['value' => 'block', 'label' => __('Block Mode')],
            ['value' => 'allow', 'label' => __('Allow Mode')]
        ];
    }

    /**
     * @return array
     */
    public function getAllOptions()
    {
        return $this->toOptionArray();
    }
}
