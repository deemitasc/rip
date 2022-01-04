<?php
namespace Ripen\UseRegionAbbreviations\Plugin;

use Magento\Framework\DataObject;
use Magento\Directory\Model\RegionFactory;
use Magento\Store\Model\Information;

class ConvertRegion
{
    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        RegionFactory $regionFactory
    ) {
        $this->regionFactory = $regionFactory;
    }

    /**
     * @param \Magento\Store\Model\Information $subject
     * @param DataObject $storeInfo
     * @return DataObject
     */
    public function afterGetStoreInformationObject(Information $subject, DataObject $storeInfo)
    {
        $storeInfo->setRegion($this->regionFactory->create()->load($storeInfo->getRegionId())->getCode());

        return $storeInfo;
    }
}
