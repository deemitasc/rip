<?php

namespace Vekeryk\SaveCart\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Vekeryk\SaveCart\Model\SaveCart as SaveCartModel;
use Vekeryk\SaveCart\Model\SaveCartFactory as SaveCartModelFactory;
use Vekeryk\SaveCart\Model\SaveCartCustomerQuoteFactory as SaveCartCustomerQuoteModelFactory;

/**
 * Class SaveCartRepository
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveCartRepository implements \Vekeryk\SaveCart\Api\SaveCartRepositoryInterface
{
    /**
     * @var SaveCartModel[]
     */
    protected $quotesById = [];

    /**
     * @var ResourceModel\SaveCart
     */
    protected $resourceModel;

    /**
     * @var ResourceModel\SaveCartCustomerQuote
     */
    protected $resourceCustomerQuote;

    /**
     * @var SaveCartModelFactory
     */
    protected $saveCartModelFactory;

    /**
     * @var SaveCartCustomerQuoteFactory
     */
    protected $saveCartCustomerQuoteModelFactory;

    /**
     * @param \Vekeryk\SaveCart\Model\ResourceModel\SaveCart $resourceModel
     * @param \Vekeryk\SaveCart\Model\ResourceModel\SaveCartCustomerQuote $resourceCustomerQuote
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Vekeryk\SaveCart\Model\ResourceModel\SaveCart $resourceModel,
        \Vekeryk\SaveCart\Model\ResourceModel\SaveCartCustomerQuote $resourceCustomerQuote,
        SaveCartModelFactory $saveCartModelFactory,
        SaveCartCustomerQuoteModelFactory $saveCartCustomerQuoteModelFactory
    ) {
        $this->resourceModel = $resourceModel;
        $this->resourceCustomerQuote = $resourceCustomerQuote;
        $this->saveCartModelFactory = $saveCartModelFactory;
        $this->saveCartCustomerQuoteModelFactory = $saveCartCustomerQuoteModelFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function get($cartId)
    {
        if (!isset($this->quotesById[$cartId])) {
            $savedCart = $this->saveCartModelFactory->create();
            $this->resourceModel->load($savedCart, $cartId, 'quote_id');

            if (!$savedCart->getId()) {
                throw new NoSuchEntityException(__('Requested saved cart doesn\'t exist'));
            }

            $this->quotesById[$cartId] = $savedCart;
        }
        return $this->quotesById[$cartId];
    }

    /**
     * @inheritdoc
     */
    public function save(\Vekeryk\SaveCart\Api\Data\SaveCartInterface $saveCart)
    {
        $quoteId = $saveCart->getQuoteId();
        try {
            $existingSavedCart = $this->get($quoteId);
            $existingSavedCart->setQuoteName($saveCart->getQuoteName());
            $existingSavedCart->setQuoteComment($saveCart->getQuoteComment());
            $existingSavedCart->setCustomerId($saveCart->getCustomerId());
        } catch (NoSuchEntityException $e) {
            $existingSavedCart = $saveCart;
        }

        unset($this->quotesById[$quoteId]);

        try {
            $this->resourceModel->save($existingSavedCart);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__('Unable to save cart'));
        }

        return $this->get($quoteId);
    }

    /**
     * @inheritdoc
     */
    public function setCurrentQuotePointer($customerId, $quoteId)
    {
        /**
         * @var SaveCartCustomerQuote $customerQuote
         */
        $customerQuote = $this->saveCartCustomerQuoteModelFactory->create();
        $this->resourceCustomerQuote->load($customerQuote, $customerId, 'customer_id');

        $customerQuote->setCustomerId($customerId)
            ->setQuoteId($quoteId);

        $this->resourceCustomerQuote->save($customerQuote);
    }

    /**
     * @inheritdoc
     */
    public function getCurrentQuotePointer($customerId)
    {
        return $this->resourceCustomerQuote->getCurrentQuotePointer($customerId);
    }

    /**
     * @inheritdoc
     */
    public function removeCurrentQuotePointer($customerId)
    {
        return $this->resourceCustomerQuote->removeCurrentQuotePointer($customerId);
    }

    /**
     * @inheritdoc
     */
    public function isQuoteSaved($cartId)
    {
        try {
            return $this->get($cartId);
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(\Vekeryk\SaveCart\Api\Data\SaveCartInterface $saveCart)
    {
        unset($this->quotesById[$saveCart->getId()]);

        try {
            $this->resourceModel->delete($saveCart);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\StateException(
                __('Unable to remove saved cart')
            );
        }

        return true;
    }
}
