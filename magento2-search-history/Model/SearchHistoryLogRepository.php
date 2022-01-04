<?php

namespace Ripen\SearchHistory\Model;

class SearchHistoryLogRepository implements \Ripen\SearchHistory\Api\SearchHistoryLogRepositoryInterface
{
    /**
     * @var SearchHistoryLogFactory
     */
    protected $searchHistoryLogFactory;

    /**
     * @var ResourceModel\SearchHistoryLog
     */
    protected $resourceModel;

    public function __construct(
        \Ripen\SearchHistory\Model\SearchHistoryLogFactory $searchHistoryLogFactory,
        \Ripen\SearchHistory\Model\ResourceModel\SearchHistoryLog $resourceModel
    ) {
        $this->searchHistoryLogFactory = $searchHistoryLogFactory;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @param SearchHistoryLog $searchHistoryLog
     * @return mixed|SearchHistoryLog
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(SearchHistoryLog $searchHistoryLog)
    {
        try {
            $data = [
                'query_text' => $searchHistoryLog->getData('query_text'),
                'customer_id' => $searchHistoryLog->getData('customer_id'),
            ];
            $saveData = [
                'query_text',
                'updated_at'
            ];
            $this->resourceModel->saveQuery($data, $saveData);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__($exception->getMessage()));
        }
        return $searchHistoryLog;
    }

    /**
     * @param $queryId
     * @return SearchHistoryLog
     */
    public function get($queryId)
    {
        $searchHistoryLog = $this->searchHistoryLogFactory->create();
        $searchHistoryLog->load($queryId);
        if (!$searchHistoryLog->getId()) {
            $searchHistoryLog->setId($queryId);
        }
        return $searchHistoryLog;
    }

}
