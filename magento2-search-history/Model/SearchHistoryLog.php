<?php

namespace Ripen\SearchHistory\Model;

use Magento\Search\Model\Query as QueryModel;

class SearchHistoryLog extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var SearchHistoryLogRepository
     */
    protected $searchHistoryLogRepository;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Ripen\SearchHistory\Model\SearchHistoryLogRepository $searchHistoryLogRepository
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_init('Ripen\SearchHistory\Model\ResourceModel\SearchHistoryLog');

        $this->searchHistoryLogRepository = $searchHistoryLogRepository;
    }

    /**
     * @param QueryModel $query
     * @param null $customerId
     * @return $this
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveQuery(QueryModel $query, $customerId = NULL)
    {
        $this->setData('query_text', trim($query->getQueryText()));
        $this->setData('customer_id', $customerId);
        $this->searchHistoryLogRepository->save($this);

        return $this;
    }
}
