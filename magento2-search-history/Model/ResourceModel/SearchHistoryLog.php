<?php

namespace Ripen\SearchHistory\Model\ResourceModel;


class SearchHistoryLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('ripen_search_history_log', 'query_id');
    }

    /**
     * @param array $data
     * @param array $updateData
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveQuery($data, $updateData)
    {
        $connection = $this->getConnection();

        $connection->insertOnDuplicate(
            $this->getMainTable(),
            $data,
            $updateData
        );
    }
}
