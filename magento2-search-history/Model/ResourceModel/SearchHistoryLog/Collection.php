<?php

namespace Ripen\SearchHistory\Model\ResourceModel\SearchHistoryLog;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'Ripen\SearchHistory\Model\SearchHistoryLog',
            'Ripen\SearchHistory\Model\ResourceModel\SearchHistoryLog'
        );
    }
}
