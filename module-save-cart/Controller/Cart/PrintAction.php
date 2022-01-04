<?php

namespace Vekeryk\SaveCart\Controller\Cart;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Registry;

class PrintAction extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Sales\Controller\AbstractController\OrderLoaderInterface
     */
    protected $orderLoader;

    private $quoteRepository;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    private $resultForwardFactory;

    private $registry;

    /**
     * @param Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        CartRepositoryInterface $quoteRepository,
        //OrderLoaderInterface $orderLoader,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        Registry $registry
    ) {
        parent::__construct($context);
        //$this->orderLoader = $orderLoader;
        $this->resultPageFactory = $resultPageFactory;
        $this->quoteRepository = $quoteRepository;
        $this->registry = $registry;
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Print Order Action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quoteId = (int)$this->getRequest()->getParam('quote_id');
        if (!$quoteId) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }

        //$quote = $this->quoteRepository->get($quoteId);

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle('print');
        return $resultPage;
    }
}
