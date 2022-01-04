<?php

namespace Ripen\SearchHistory\Controller\Query;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Ripen\SearchHistory\Model\Config;

class History extends \Magento\Framework\App\Action\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Config $config
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);

        $this->config = $config;
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // check if the history is viewable
        if (! $this->config->isSearchLogViewable()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('My Search History'));

        $block = $resultPage->getLayout()->getBlock('customer.account.link.back');
        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }
        return $resultPage;
    }
}
