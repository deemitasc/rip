<?php

namespace Vekeryk\SaveCart\Plugin\Quote\Model\QuoteRepository;

use Vekeryk\SaveCart\Api\SaveCartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Vekeryk\SaveCart\Api\Data\SaveCartInterfaceFactory as SaveCartFactory;
use Psr\Log\LoggerInterface as Logger;

class SaveHandler
{
    /**
     * @var SaveCartRepositoryInterface
     */
    private $saveCartRepository;

    /**
     * @var CartExtensionFactory
     */
    private $cartExtensionFactory;

    private $resourceModel;

    private $saveCartFactory;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        SaveCartRepositoryInterface $saveCartRepository,
        CartExtensionFactory $cartExtensionFactory,
        \Vekeryk\SaveCart\Model\ResourceModel\SaveCart $resourceModel,
        SaveCartFactory $saveCartFactory,
        Logger $logger
    ) {
        $this->saveCartRepository = $saveCartRepository;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->resourceModel = $resourceModel;
        $this->saveCartFactory = $saveCartFactory;
        $this->logger = $logger;
    }

    public function afterSave($subject, $entity)
    {
        try {
            $extAttributes = $entity->getExtensionAttributes();
            if ($extAttributes && $extAttributes->getSaveCartData()) {
                $savedCart = $extAttributes->getSaveCartData();
                $this->saveCartRepository->save($savedCart);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }

        return $entity;
    }
}
