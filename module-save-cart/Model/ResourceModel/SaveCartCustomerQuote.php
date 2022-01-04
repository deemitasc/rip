<?php

namespace Vekeryk\SaveCart\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;

/**
 * SaveCart Customer Quote resource model
 */
class SaveCartCustomerQuote extends AbstractDb
{
    /**
     * Initialize table nad PK name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('savecart_customer_quote', 'entity_id');
    }

    /**
     * @param int $customerId
     * @return bool|int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentQuotePointer($customerId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from(['main_table' => $this->getMainTable()], 'quote_id')
            ->join(
                ['Q' => $this->getTable('quote')],
                'main_table.quote_id=Q.entity_id AND main_table.customer_id=Q.customer_id',
                []
            )
            ->where('main_table.customer_id =?', (int)$customerId)
            ->where('Q.is_active =1')
            ->limit(1);

        return $connection->fetchOne($select);
    }

    public function removeCurrentQuotePointer($customerId)
    {
        $connection = $this->getConnection();
        return $connection->delete(
            $this->getMainTable(),
            ['customer_id =?' => $customerId]
        );
    }
}
