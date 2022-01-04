<?php

namespace Ripen\SearchHistory\Plugin;

use Ripen\SearchHistory\Model\Config;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\CatalogSearch\Block\Result;
use Magento\CatalogSearch\Helper\Data;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Layer;
use Magento\Search\Model\QueryFactory;
use Ripen\SearchHistory\Model\SearchHistoryLog;

class SaveSearch
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * Catalog search data
     *
     * @var Data
     */
    protected $catalogSearchData;

    /**
     * @var Layer
     */
    protected $layer;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var SearchHistoryLog
     */
    protected $searchHistoryLog;

    public function __construct(
        Config $config,
        CurrentCustomer $currentCustomer,
        Resolver $layerResolver,
        Data $catalogSearchData,
        QueryFactory $queryFactory,
        SearchHistoryLog $searchHistoryLog
    ) {
        $this->config                       = $config;
        $this->currentCustomer              = $currentCustomer;
        $this->layer                        = $layerResolver->get();
        $this->catalogSearchData            = $catalogSearchData;
        $this->queryFactory                 = $queryFactory;
        $this->searchHistoryLog             = $searchHistoryLog;
    }

    public function aroundGetNoteMessages(Result $resultBlock, \Closure $proceed)
    {
        $messages = $proceed();
        $query    = $this->queryFactory->get();

        if ($this->canSaveQuery()) {
            $this->searchHistoryLog->saveQuery($query, $this->currentCustomer->getCustomerId());
        }

        return $messages;
    }

    /**
     * Indicates if the search query should be save or not.
     *
     * @return boolean
     */
    protected function canSaveQuery()
    {
        $canSave = false;
        if (! $this->config->isGuestSearchLoggingEnabled()) {
            $customerId = $this->currentCustomer->getCustomerId();
            if ($customerId) {
                $canSave = true;
            }
        }

        // only return true if the search does not contain any filters i.e. it's what the user entered initially
        return empty($this->layer->getState()->getFilters() && $canSave);
    }
}
