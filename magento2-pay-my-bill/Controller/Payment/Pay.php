<?php
namespace Ripen\PayMyBill\Controller\Payment;

use Magento\Framework\Exception\LocalizedException;
use Ripen\VantivIntegratedPayments\Exception\AvsCheckFailedException;
use Ripen\VantivIntegratedPayments\Exception\TransactionAuthorizationFailedException;

/**
 * @package Ripen\ManagePaymentMethods\Controller\Cards
 */
class Pay extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Ripen\VantivIntegratedPayments\Model\Api
     */
    protected $api;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Ripen\PayMyBill\Model\PaymybillLogFactory
     */
    protected $paymentLogFactory;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Ripen\VantivIntegratedPayments\Model\Api $api,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Ripen\PayMyBill\Model\PaymybillLogFactory  $paymentLogFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper
    )
    {
        parent::__construct($context);

        $this->formKeyValidator = $formKeyValidator;
        $this->pageFactory = $pageFactory;
        $this->logger = $logger;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->api = $api;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->paymentLogFactory = $paymentLogFactory;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if(!$this->scopeConfig->getValue('paymybill/general/enable_paymybill')){
            $this->messageManager->addErrorMessage("Invalid Request");
            return $this->_redirect('currentstatement/view/');
        }

        if (! $this->formKeyValidator->validate($this->_request) ||
            !is_numeric($this->_request->getPostValue('amount')) ||
            $this->_request->getPostValue('amount') <= 0
        ) {
            $result->setData(['message' => 'Invalid form submission.']);
            return $result;
        }

        try {
            $customer = $this->customerSession->getCustomer()->getDataModel();

            $amount = $this->_request->getPostValue('amount');
            $surcharge = $this->scopeConfig->getValue('paymybill/general/credit_card_surcharge');
            $newAmount = $amount * (1 + $surcharge / 100);

            $invoices = $this->_request->getPostValue('invoices');
            $invoices = $invoices ? explode(',', $invoices) : [];
            $invoiceNumbers = $this->formatInvoiceNumbers($invoices);
            $onAccount = (bool)$this->_request->getPostValue('on_account');
            $referenceNumber = $this->formatReferenceNumber($invoices, $onAccount);
            $expDate = explode('/', $this->_request->getPostValue('cc_exp'));

            if(!$onAccount && !$invoices){
                $result->setData(['message' => 'Select at least one invoice']);
                return $result;
            }

            $response = $this->api->makeCaptureRequest(
                $this->_request->getPostValue('cc_number'),
                $expDate[0],
                $expDate[1],
                $this->_request->getPostValue('cc_cvv'),
                $newAmount,
                $referenceNumber
            );

            // Temporarily disable AVS check until it's fully tested
            /*
            if (!$this->api->isAvsCheckPassed($response)) {
                $result->setData(['message' => (string)$this->api->getApiAvsFailedUserMessage()]);
                return $result;
            }
            */

            if (!$this->api->isTransactionApproved($response)) {
                $result->setData(['message' => (string)$this->api->getResponseMessage($response)]);
                return $result;
            }

            $transactionId = (string)$this->api->getTransactionId($response);
            $erpCustomerId = $customer->getCustomAttribute('erp_customer_id');

            $paymentLogRecord = $this->paymentLogFactory->create();
            $paymentLogRecord->setCustomerId($customer->getId());
            $paymentLogRecord->setErpCustomerId($erpCustomerId ? $erpCustomerId->getValue() : NULL);
            $paymentLogRecord->setTransactionId($transactionId);
            $paymentLogRecord->setInvoices($invoiceNumbers);
            $paymentLogRecord->setAmount($amount);
            $paymentLogRecord->setAmountWithSurcharge($newAmount);
            $paymentLogRecord->setResponseCode((string)$this->api->getResponseCode($response));
            $paymentLogRecord->setResponse(json_encode($response));
            $paymentLogRecord->save();

            $this->sendEmailNotification($customer, $response, $onAccount, $invoiceNumbers);

            $message = "Balance payment has been successfully submitted. Transaction ID: {$transactionId}";
            $this->messageManager->addSuccessMessage($message);
            $result->setData(['url' => $this->storeManager->getStore()->getBaseUrl().'currentstatement/view/']);

            return $result;

        } catch (LocalizedException $e) {
            $result->setData(['message' => $e->getMessage()]);
            return $result;
        } catch (\Exception $e) {
            $result->setData(['message' => __('An error occurred, please check the information entered and try again. %1', $e->getMessage())]);
            return $result;
        }
    }

    public function formatReferenceNumber($invoices, $onAccount){
        $refNumber = 'INV-BALANCE-'.date('Y-m-d H:i');
        if (!$onAccount && $invoices){
            $invoiceNumbers = implode(',', $invoices);
            $refNumber = "INV-".$invoiceNumbers;

            // Current Vantiv's character limit for reference number field is 50
            $charLimit = 50;
            if(strlen($refNumber) > $charLimit){
                $refNumber = substr($refNumber, 0, $charLimit);
                if(substr($refNumber, -1) == ','){
                    $refNumber = substr($refNumber, 0, -1);
                } else {
                    $refNumber = substr(substr($refNumber, 0, -3), 0, strrpos($refNumber,','))."...";
                }
            }
        }

        return $refNumber;
    }


    public function formatInvoiceNumbers($invoices){
        return $invoices ? implode(', ', $invoices) : 'n/a';
    }

    protected function sendEmailNotification($customer, $response, $onAccount, $invoiceNumbers = NULL){

        $erpCustomerId = $customer->getCustomAttribute('erp_customer_id');
        $transactionId = (string)$this->api->getTransactionId($response);

        $ccNumber = (string)$this->api->getMaskedCard($response);
        $ccExpMonth = (string)$this->api->getExpirationMonth($response);
        $ccExpYear = (string)$this->api->getExpirationYear($response);
        $amount = (string)$this->api->getApprovedAmount($response);

        // Send notification email
        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
        $templateVars = array(
            'transaction_id' => $transactionId,
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'email' => $customer->getEmail(),
            'erp_customer_id' => $erpCustomerId ? $erpCustomerId->getValue() : 'n/a',
            'cc_number' => $ccNumber,
            'amount' => $this->priceHelper->currency($amount, true, false),
            'cc_exp_month' => $ccExpMonth,
            'cc_exp_year' => $ccExpYear,
            'on_account' => $onAccount ? 'Yes' : 'No',
            'invoice_numbers' => $invoiceNumbers ?: 'n/a'
        );

        $from = array('email' => $this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), 'name' => 'Ritz Safety');
        $emails = $this->getRecipientEmail();
        $transport = $this->transportBuilder->setTemplateIdentifier('paymybill_general_balance_payment_notification_template')
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($emails)
            ->getTransport();

        $transport->sendMessage();
    }

    public function getRecipientEmail(){
        $paymybillEmails = $this->scopeConfig->getValue('paymybill/general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($paymybillEmails){
            $paymybillEmails = explode(',', $paymybillEmails);
            $emails = array_map('trim', $paymybillEmails);
        } else {
            $emails = array($this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        }

        return $emails;
    }
}
