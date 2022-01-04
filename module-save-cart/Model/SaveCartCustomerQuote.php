<?php

namespace Vekeryk\SaveCart\Model;

use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\ResourceModel;
use Magento\Sales\Model\Status;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Vekeryk\SaveCart\Api\Data\SaveCartInterface;
use Vekeryk\SaveCart\Api\SaveCartRepositoryInterface;

/**
 * SaveCart model
 *
 */
class SaveCartCustomerQuote extends \Magento\Framework\Model\AbstractModel
{
    const QUOTE_NAME = 'quote_name';

    const QUOTE_COMMENT = 'quote_comment';

    const QUOTE_ID = 'quote_id';

    const CUSTOMER_ID = 'customer_id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vekeryk\SaveCart\Model\ResourceModel\SaveCartCustomerQuote');
    }


    /**
     * @inheritdoc
     */
    public function getQuoteName()
    {
        return $this->_getData(self::QUOTE_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setQuoteName($name)
    {
        return $this->setData(self::QUOTE_NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getQuoteComment()
    {
        return $this->_getData(self::QUOTE_COMMENT);
    }

    /**
     * @inheritdoc
     */
    public function setQuoteComment($comment)
    {
        return $this->setData(self::QUOTE_COMMENT, $comment);
    }

    /**
     * @inheritdoc
     */
    public function getQuoteId()
    {
        return $this->_getData(self::QUOTE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setQuoteId($id)
    {
        return $this->setData(self::QUOTE_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId()
    {
        return $this->_getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($id)
    {
        return $this->setData(self::CUSTOMER_ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->_getData(self::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt()
    {
        return $this->_getData(self::UPDATED_AT);
    }
}
