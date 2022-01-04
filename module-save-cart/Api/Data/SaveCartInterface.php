<?php

namespace Vekeryk\SaveCart\Api\Data;

/**
 * @api
 */
interface SaveCartInterface
{
    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return int|null
     */
    public function getQuoteId();

    /**
     * @param int $id
     * @return $this
     */
    public function setQuoteId($id);

    /**
     * @return int|null
     */
    public function getCustomerId();

    /**
     * @param int $id
     * @return $this
     */
    public function setCustomerId($id);

    /**
     * Get quote name
     *
     * @return string
     */
    public function getQuoteName();

    /**
     * Set quote name
     *
     * @param string $name
     * @return $this
     */
    public function setQuoteName($name);


    /**
     * Get quote comment
     *
     * @return string
     */
    public function getQuoteComment();

    /**
     * Set quote comment
     *
     * @param string $comment
     * @return $this
     */
    public function setQuoteComment($comment);

    /**
     * Returns the cart creation date and time.
     *
     * @return string|null Cart creation date and time. Otherwise, null.
     */
    public function getCreatedAt();

    /**
     * Returns the cart last update date and time.
     *
     * @return string|null Cart last update date and time. Otherwise, null.
     */
    public function getUpdatedAt();
}
