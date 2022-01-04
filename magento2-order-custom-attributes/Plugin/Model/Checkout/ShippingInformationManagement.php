<?php
namespace Ripen\OrderCustomAttributes\Plugin\Model\Checkout;

class ShippingInformationManagement
{
    protected $quoteRepository;
    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->jsonHelper = $jsonHelper;
        $this->filterManager = $filterManager;
        $this->productRepository = $productRepository;
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
        $this->scopeConfiguration = $scopeConfiguration;
    }

    /**
     * Save custom attributes to quote object and database
     *
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $quote = $this->quoteRepository->getActive($cartId);

        $comments = $addressInformation->getExtensionAttributes()->getComments();
        if ($comments) {
            $comments = $this->filterManager->stripTags($comments);
            $quote->setComments($comments);
        }
        $poNumber = $addressInformation->getExtensionAttributes()->getPoNumber();
        if ($poNumber) {
            $poNumber = $this->filterManager->stripTags($poNumber);
            $quote->setPoNumber($poNumber);
        }
        
        $shipEntireOnly = $addressInformation->getExtensionAttributes()->getShipEntireOnly();
        $quote->setShipEntireOnly($shipEntireOnly);
    }
}
