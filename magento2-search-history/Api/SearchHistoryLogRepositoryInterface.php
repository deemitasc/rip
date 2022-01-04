<?php
namespace Ripen\SearchHistory\Api;

use Ripen\SearchHistory\Model\SearchHistoryLog;

interface SearchHistoryLogRepositoryInterface
{
    /**
     * @param SearchHistoryLog $searchHistoryLog
     * @return mixed
     */
    public function save(SearchHistoryLog $searchHistoryLog);

    /**
     * @param $queryId
     * @return SearchHistoryLog
     */
    public function get($queryId);
}
