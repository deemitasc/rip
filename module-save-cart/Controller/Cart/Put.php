<?php

namespace Vekeryk\SaveCart\Controller\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Vekeryk\SaveCart\Controller\AbstractCart;
use Vekeryk\SaveCart\Api\SaveCartRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;

class Put extends AbstractCart
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var SaveCartRepositoryInterface
     */
    private $saveCartRepository;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        SaveCartRepositoryInterface $saveCartRepository,
        CustomerCart $cart,
        Context $context,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context, $customerSession);
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->formKeyValidator = $formKeyValidator;
        $this->saveCartRepository = $saveCartRepository;
        $this->cart = $cart;
    }

    private function addToCart($quote)
    {
        $this->cart->truncate()->save();

        $this->saveCartRepository->setCurrentQuotePointer($quote->getCustomerId(), $quote->getId());
        $this->checkoutSession->replaceQuote($quote);
        $this->checkoutSession->setCartWasUpdated(true);
    }

    private function deleteCart($quote)
    {
        $extAttributes = $quote->getExtensionAttributes();
        if ($extAttributes) {
            $savedCart = $extAttributes->getSaveCartData();
            $this->saveCartRepository->delete($savedCart);
        }

        $quote->setCustomerId(new \Zend_Db_Expr('NULL'))
            ->setCustomerEmail(new \Zend_Db_Expr('NULL'))
            ->save();

        //clear quote if current
        $this->checkoutSession->clearQuote()->clearStorage();
    }

    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $quoteId = (int)$this->getRequest()->getParam('quote_id');
        $cartAction = (string)$this->getRequest()->getParam('cart_action');

        $redirectPath = 'checkout/cart';

        try {
            $quote = $this->quoteRepository->get($quoteId);
            $customerId = $this->customerSession->getCustomerId();
            if ($quote->getCustomerId() == $customerId) {
                switch ($cartAction) {
                    case 'delete_cart':
                        $this->deleteCart($quote);
                        $redirectPath = '*/*/';
                        break;
                    case 'add_to_cart':
                        $this->addToCart($quote);
                        break;
                }
            } else {
                throw NoSuchEntityException::doubleField('customerId', $customerId, 'quoteId', $quoteId);
            }
        } catch (\Exception $e) {
            $this->messageManager->addError('Could not update shopping cart.');
            $redirectPath = '*/*/';
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath($redirectPath);
    }
}
