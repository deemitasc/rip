<?php

namespace Vekeryk\SaveCart\Plugin\Quote\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Vekeryk\SaveCart\Api\SaveCartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Psr\Log\LoggerInterface as Logger;

class CartRepository
{
    /**
     * @var SaveCartRepositoryInterface
     */
    private $saveCartRepository;

    /**
     * @var CartExtensionFactory
     */
    private $cartExtensionFactory;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        SaveCartRepositoryInterface $saveCartRepository,
        CartExtensionFactory $cartExtensionFactory,
        Logger $logger
    ) {
        $this->saveCartRepository = $saveCartRepository;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Quote\Api\CartRepositoryInterface $subject
     * @param callable $proceed
     * @param int $customerId
     * @param array $sharedStoreIds
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundGetForCustomer($subject, callable $proceed, $customerId, array $sharedStoreIds = [])
    {
        $quoteId = $this->saveCartRepository->getCurrentQuotePointer($customerId);
        if (!$quoteId) {
            return $proceed($customerId, $sharedStoreIds);
        } else {
            return $subject->getActive($quoteId);
        }
    }

    public function afterGet($subject, $entity)
    {
        try {
            $savedCart = $this->saveCartRepository->get($entity->getId());
            $extensionAttributes = $entity->getExtensionAttributes();

            if ($extensionAttributes === null) {
                $extensionAttributes = $this->cartExtensionFactory->create();
            }

            $extensionAttributes->setSaveCartData($savedCart);
            $entity->setExtensionAttributes($extensionAttributes);
        } catch (NoSuchEntityException $e) {
            //do nothing
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }

        return $entity;
    }
}
