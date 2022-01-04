<?php

namespace Vekeryk\SaveCart\Controller\Cart;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Vekeryk\SaveCart\Api\SaveCartRepositoryInterface;
use Vekeryk\SaveCart\Model\SaveCartFactory as SaveCartModelFactory;
use Magento\Framework\App\RequestInterface;

class UpdatePost extends \Magento\Checkout\Controller\Cart
{
	/**
	 * @var CartRepositoryInterface
	 */
	private $quoteRepository;

	/**
	 * @var SaveCartRepositoryInterface
	 */
	private $saveCartRepository;

	/**
	 * @var \Magento\Customer\Model\Session
	 */
	private $customerSession;

	/**
	 * @var SaveCartModelFactory
	 */
	private $saveCartModelFactory;

	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
	 * @param CustomerCart $cart
	 * @param CartRepositoryInterface $quoteRepository
	 */
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
		CustomerCart $cart,
		CartRepositoryInterface $quoteRepository,
		SaveCartRepositoryInterface $saveCartRepository,
		\Magento\Customer\Model\Session $customerSession,
		SaveCartModelFactory $saveCartModelFactory
	) {
		parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart);
		$this->quoteRepository = $quoteRepository;
		$this->saveCartRepository = $saveCartRepository;
		$this->customerSession = $customerSession;
		$this->saveCartModelFactory = $saveCartModelFactory;
	}

	/**
	 * Check customer authentication for some actions
	 *
	 * @param RequestInterface $request
	 * @return \Magento\Framework\App\ResponseInterface
	 */
	public function dispatch(RequestInterface $request)
	{
		if (!$this->customerSession->authenticate()) {
			$this->_actionFlag->set('', 'no-dispatch', true);
		}
		return parent::dispatch($request);
	}

	/**
	 * Update customer's shopping cart
	 *
	 * @return void
	 */
	protected function _updateShoppingCart()
	{
		try {
			$cartData = $this->getRequest()->getParam('cart');
			if (is_array($cartData)) {
				$filter = new \Zend_Filter_LocalizedToNormalized(
					['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()]
				);
				foreach ($cartData as $index => $data) {
					if (isset($data['qty'])) {
						$cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
					}
				}
				if (!$this->cart->getCustomerSession()->getCustomerId() && $this->cart->getQuote()->getCustomerId()) {
					$this->cart->getQuote()->setCustomerId(null);
				}

				$cartData = $this->cart->suggestItemsQty($cartData);
				$this->setSaveCartData($cartData);
				$this->cart->updateItems($cartData);
				//->save();
				//avoid call save method because it update current quote
				$this->cart->getQuote()->getBillingAddress();
				$this->cart->getQuote()->getShippingAddress()->setCollectShippingRates(true);
				$this->cart->getQuote()->collectTotals();
				$this->quoteRepository->save($this->cart->getQuote());
			}
		} catch (\Magento\Framework\Exception\LocalizedException $e) {
			$this->messageManager->addError(
				$this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage())
			);
		}
	}

	protected function _deleteItem()
	{
		$id = (int)$this->getRequest()->getParam('quote_item_id');
		if ($id) {
			try {
				$this->cart->removeItem($id);
				if (!$this->cart->getCustomerSession()->getCustomerId() && $this->cart->getQuote()->getCustomerId()) {
					$this->cart->getQuote()->setCustomerId(null);
				}
				$this->quoteRepository->save($this->cart->getQuote());
			} catch (\Exception $e) {
				$this->messageManager->addError(
					$this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e)
				);
			}
		}
	}

	protected function setSaveCartData($cartData)
	{
		$quote = $this->cart->getQuote();
		try {
			$savedCart = $this->saveCartRepository->get($quote->getId());
		} catch (NoSuchEntityException $e) {
			$savedCart = $this->saveCartModelFactory->create();
			$savedCart->setQuoteId($quote->getId());
		}
		$savedCart->setQuoteName($cartData['quote_name']);
		$savedCart->setQuoteComment($cartData['quote_comment']);
		$savedCart->setCustomerId($quote->getCustomerId());

		$extensionAttributes = $quote->getExtensionAttributes();

		if ($extensionAttributes === null) {
			$extensionAttributes = $this->cartExtensionFactory->create();
		}

		$extensionAttributes->setSaveCartData($savedCart);
		$quote->setExtensionAttributes($extensionAttributes);
	}

	/**
	 * Update shopping cart data action
	 *
	 * @return \Magento\Framework\Controller\Result\Redirect
	 */
	public function execute()
	{
		if (!$this->_formKeyValidator->validate($this->getRequest())) {
			return $this->resultRedirectFactory->create()->setPath('*/*/');
		}

		$updateAction = (string)$this->getRequest()->getParam('update_cart_action');
		$quoteId = $this->getRequest()->getParam('quote_id');

		try {
			$quote = $this->quoteRepository->get($quoteId);
			if ($quote->getCustomerId() == $this->customerSession->getCustomerId()) {
				$this->cart->setQuote($quote);

				if ($updateAction == 'remove_item') {
					$this->_deleteItem();

					$this->messageManager->addSuccessMessage(
						__('Item has been removed.')
					);
				}
				else {
					$this->_updateShoppingCart();

					switch ($updateAction) {
						case 'save':
							$this->_checkoutSession->clearQuote()->clearStorage();
							$this->messageManager->addSuccessMessage(
								__('The main cart contents has been transferred to the quote.')
							);
							$resultRedirect = $this->resultRedirectFactory->create();
							$resultRedirect->setPath('savecart/cart');
							return $resultRedirect;
						case 'update':
							$this->messageManager->addSuccessMessage(
								__('Quote has been updated.')
							);
							break;
					}
				}
			}
		} catch (NoSuchEntityException $e) {
			$this->messageManager->addException($e, __('We can\'t update the shopping cart.'));
		} catch (\Exception $e) {
			$this->messageManager->addException($e, __('We can\'t update the shopping cart.'));
			$this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
		}

		return $this->_goBack();
	}
}
