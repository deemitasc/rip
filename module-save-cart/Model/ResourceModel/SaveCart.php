<?php

namespace Vekeryk\SaveCart\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;

/**
 * SaveCart resource model
 */
class SaveCart extends AbstractDb
{
    /**
     * Initialize table nad PK name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('savecart', 'entity_id');
    }

    public function addSavedCartInfoToCollection($collection)
    {
        $collection->getSelect()->join(
            ['VSC' => $this->getMainTable()],
            'main_table.entity_id=VSC.quote_id',
            [
                'quote_name',
                'quote_comment',
                'vsc_created_at' => 'created_at',
                'vsc_updated_at' => 'updated_at'
            ]
        );
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function prepareDataForUpdate($object)
    {
        $data = parent::prepareDataForUpdate($object);

        if (isset($data['updated_at'])) {
            unset($data['updated_at']);
        }

        return $data;
    }

    /**
     * Load quote data by customer identifier
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param int $customerId
     * @return $this
     */
    public function loadByCustomerId($quote, $customerId)
    {
        $field = $this->getConnection()->quoteIdentifier(sprintf('%s.%s', $this->getTable('quote'), 'customer_id'));
        $select = $this->getConnection()->select()->from($this->getTable('quote'))->where($field . '=?', $customerId);
        $storeIds = $quote->getSharedStoreIds();
        if ($storeIds) {
            if ($storeIds != ['*']) {
                $select->where('store_id IN (?)', $storeIds);
            }
        } else {
            /**
             * For empty result
             */
            $select->where('store_id < ?', 0);
        }

        $connection = $this->getConnection();
        $select->joinLeft(
            ['SC' => $this->getMainTable()],
            'quote.entity_id=SC.quote_id',
            []
        )->where(
            'SC.entity_id IS NULL'//to exclude saved cart
        )->where(
            'is_active = ?',
            1
        )->order(
            'updated_at ' . \Magento\Framework\DB\Select::SQL_DESC
        )->limit(
            1
        );

        $data = $connection->fetchRow($select);

        if ($data) {
            $quote->setData($data);
        }

        $this->_afterLoad($quote);

        return $this;
    }
}
