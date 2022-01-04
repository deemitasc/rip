<?php

namespace Vekeryk\SaveCart\Api;

/**
 * Interface SaveCartRepositoryInterface
 * @api
 */
interface SaveCartRepositoryInterface
{
    /**
     * Enables an administrative user to return information for a specified cart.
     *
     * @param int $cartId
     * @return Data\SaveCartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($cartId);

    /**
     * @param Data\SaveCartInterface $saveCart
     * @return Data\SaveCartInterface
     */
    public function save(Data\SaveCartInterface $saveCart);

    /**
     * @param Data\SaveCartInterface $saveCart
     * @return void
     */
    public function delete(Data\SaveCartInterface $saveCart);

    /**
     * @param int $customerId
     * @return int
     */
    public function getCurrentQuotePointer($customerId);

    /**
     * @param int $customerId
     * @param int $quoteId
     * @return void
     */
    public function setCurrentQuotePointer($customerId, $quoteId);

    /**
     * @param int $customerId
     * @return int
     */
    public function removeCurrentQuotePointer($customerId);

    /**
     * @param int $cartId
     * @return bool|Data\SaveCartInterface
     */
    public function isQuoteSaved($cartId);
}
