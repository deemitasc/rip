<?php

// @codingStandardsIgnoreFile

namespace Vekeryk\SaveCart\Plugin\Quote\Model\ResourceModel;

use \Vekeryk\SaveCart\Model\ResourceModel\SaveCart as SaveCartResource;

/**
 * Quote resource model
 */
class Quote
{
    /**
     * @var SaveCartResource
     */
    private $resourceModel;

    public function __construct(
        SaveCartResource $resourceModel
    ) {
        $this->resourceModel = $resourceModel;
    }

    public function aroundLoadByCustomerId($subject, callable $proceed, $quote, $customerId)
    {
        $this->resourceModel->loadByCustomerId($quote, $customerId);
        return $subject;
    }
}
