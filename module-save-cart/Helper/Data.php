<?php

namespace Vekeryk\SaveCart\Helper;

use Magento\Framework\App\Helper\Context;
use Vekeryk\SaveCart\Api\SaveCartRepositoryInterface;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var SaveCartRepositoryInterface
     */
    private $saveCartRepository;

    public function __construct(
        SaveCartRepositoryInterface $saveCartRepository,
        Context $context
    ) {
        parent::__construct($context);
        $this->saveCartRepository = $saveCartRepository;
    }

    /**
     * @param object $quote
     * @return string
     */
    public function getQuoteNumber($quote)
    {
        return 'Q' . str_pad($quote->getId(), 9, '0', STR_PAD_LEFT);
    }

    /**
     * @param int $cartId
     * @return bool|\Vekeryk\SaveCart\Api\Data\SaveCartInterface
     */
    public function isQuoteSaved($cartId)
    {
        return $this->saveCartRepository->isQuoteSaved($cartId);
    }
}
