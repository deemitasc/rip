<?php

namespace Vekeryk\SaveCart\Plugin\Checkout\Model;

use Vekeryk\SaveCart\Api\SaveCartRepositoryInterface;

class Cart
{
    /**
     * @var SaveCartRepositoryInterface
     */
    private $saveCartRepository;

    public function __construct(
        SaveCartRepositoryInterface $saveCartRepository
    ) {
        $this->saveCartRepository = $saveCartRepository;
    }

    /**
     * @param \Magento\Checkout\Model\Cart $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundTruncate($subject, callable $proceed)
    {
        $quote = $subject->getQuote();
        $savedCart = $this->saveCartRepository->isQuoteSaved($quote->getId());
        if ($savedCart) {
            $this->saveCartRepository->removeCurrentQuotePointer($quote->getCustomerId());
            $subject->getCheckoutSession()
                ->clearQuote()
                ->clearStorage();
            $subject->unsetData('quote');
            return $subject;
        } else {
            return $proceed();
        }
    }
}
