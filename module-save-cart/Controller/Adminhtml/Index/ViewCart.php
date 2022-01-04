<?php

namespace Vekeryk\SaveCart\Controller\Adminhtml\Index;

use Magento\Framework\Exception\NoSuchEntityException;

class ViewCart extends \Magento\Backend\App\Action
{

    private $resultPageFactory;

    private $coreRegistry;

    private $quoteRepository;

    private $saveCartHelper;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Vekeryk\SaveCart\Helper\Data $saveCartHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->quoteRepository = $quoteRepository;
        $this->saveCartHelper = $saveCartHelper;
    }

    private function initQuote()
    {
        $quoteId = (int)$this->getRequest()->getParam('quote_id');
        try {
            $quote = $this->quoteRepository->get($quoteId);
            $this->coreRegistry->register('current_customer_id', $quote->getId());
        } catch (NoSuchEntityException $e) {
            $quote = null;
        }

        return $quote;
    }

    /**
     * Customer orders grid
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $quote = $this->initQuote();
        $resultLayout = $this->resultPageFactory->create();
        if ($quote) {
            $resultLayout->getConfig()->getTitle()->set(__('Cart #') . ' ' . $this->saveCartHelper->getQuoteNumber($quote));
        }
        return $resultLayout;
    }
}
