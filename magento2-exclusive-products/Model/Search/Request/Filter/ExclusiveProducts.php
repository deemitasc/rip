<?php

namespace Ripen\ExclusiveProducts\Model\Search\Request\Filter;

use Magento\Framework\Exception\LocalizedException;
use Ripen\ExclusiveProducts\Model\Customer as CustomerHelper;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\FilterInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

class ExclusiveProducts implements FilterInterface
{
    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @param QueryFactory $queryFactory
     * @param CustomerHelper $customerHelper
     */
    public function __construct(
        QueryFactory $queryFactory,
        CustomerHelper $customerHelper
    ) {
        $this->queryFactory = $queryFactory;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @return QueryInterface
     * @throws LocalizedException
     */
    public function getFilterQuery()
    {
        $customerIdentifier = $this->customerHelper->getCustomerIdentifier();

        $filters = [
            $this->getExclusiveProductFilterQuery($customerIdentifier)
        ];

        $restrictionMode = $this->customerHelper->getCustomerRestrictionMode();
        if ($this->customerHelper->isRestrictionModeBlock($restrictionMode)) {
            $filters[] = $this->getCustomerBlockedProductFilterQuery($customerIdentifier);
        }
        if ($this->customerHelper->isRestrictionModeAllow($restrictionMode)) {
            $filters[] = $this->getCustomerAllowedProductFilterQuery($customerIdentifier);
        }

        return $this->queryFactory->create(
            QueryInterface::TYPE_BOOL,
            [
                'must' => $filters
            ]
        );
    }

    /**
     * Include only products that are non-exclusive or exclusive to current customer
     *
     * @param $customerIdentifier
     * @return QueryInterface
     */
    protected function getExclusiveProductFilterQuery($customerIdentifier)
    {
        // include non-exclusive products
        $filters = [
            $this->queryFactory->create(
                QueryInterface::TYPE_MISSING,
                ['field' => 'exclusive_to']
            ),
            $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                ['field' => 'exclusive_to', 'value' => '']
            )
        ];

        // if customer is logged in, include products exclusive to the customer
        if ($customerIdentifier) {
            $filters[] = $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                ['field' => 'exclusive_to', 'value' => $customerIdentifier]
            );
        }

        return $this->queryFactory->create(
            QueryInterface::TYPE_BOOL,
            ['should' => $filters]
        );
    }

    /**
     * Exclude results that are blocked for the current customer.
     *
     * @param $customerIdentifier
     * @return QueryInterface
     */
    protected function getCustomerBlockedProductFilterQuery($customerIdentifier)
    {
        return $this->queryFactory->create(
            QueryInterface::TYPE_NOT,
            [
                'query' => $this->queryFactory->create(
                    QueryInterface::TYPE_TERM,
                    [
                        'field' => 'blocked_for',
                        'value' => $customerIdentifier
                    ]
                )
            ]
        );
    }

    /**
     * Limit results to those explicitly allowed for the current customer.
     *
     * @param $customerIdentifier
     * @return QueryInterface
     */
    protected function getCustomerAllowedProductFilterQuery($customerIdentifier)
    {
        return $this->queryFactory->create(
            QueryInterface::TYPE_TERM,
            [
                'field' => 'allowed_for',
                'value' => $customerIdentifier
            ]
        );
    }
}
