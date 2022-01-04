<?php

namespace Ripen\SearchHistory\Block\Query;


class History extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'Magento_Theme::search/history.phtml';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Ripen\SearchHistory\Model\ResourceModel\SearchHistoryLog\CollectionFactory
     */
    protected $searchHistoryCollectionFactory;

    /**
     * @var \Ripen\SearchHistory\Model\ResourceModel\SearchHistoryLog\Collection
     */
    protected $searchHistory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Ripen\SearchHistory\Model\ResourceModel\SearchHistoryLog\CollectionFactory $searchHistoryCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->customerSession = $customerSession;
        $this->searchHistoryCollectionFactory = $searchHistoryCollectionFactory;
    }

    public function getSearchHistory()
    {
        if (!($customerId = $this->customerSession->getCustomerId())) {
            return false;
        }

        if (!$this->searchHistory) {

            $this->searchHistory = $this->searchHistoryCollectionFactory->create()
                ->addFieldToSelect(
                    '*'
                )->addFieldToFilter(
                    'customer_id', $customerId
                )->setOrder(
                    'updated_at',
                    'desc'
                );
        }

        return $this->searchHistory;
    }
}
