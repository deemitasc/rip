<?php
namespace Ripen\InvoicePayment\Model;

use Magento\Framework\App\Area;

/**
 * Pay In Store payment method model
 */
class Invoicepayment extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'invoicepayment';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\App\State $appState,
        \Ripen\SimpleApps\Model\Api $api
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        $this->appState = $appState;
        $this->api = $api;
        $this->scopeConfig = $scopeConfig;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($this->appState->getAreaCode() === Area::AREA_ADMINHTML || $this->appState->getAreaCode() === Area::AREA_CRONTAB) {
            return true;
        }

        $validateCustomer = $this->scopeConfig->getValue('payment/invoicepayment/validate_customer');
        if ($validateCustomer) {
            $customer = $quote->getCustomer();
            $erpCustomerId = $customer->getCustomAttribute('erp_customer_id');
            if (! $erpCustomerId) {
                return false;
            }
            $p21Customer = $this->api->getCustomer($erpCustomerId->getValue());
            if (! $p21Customer) {
                return false;
            }
 
            // Always check these properties are they are intrinsic to ability to pay on account.
            $ccPaymentRequired = $this->api->parseCustomerRequiredPaymentUponRelease($p21Customer);
            $netDays = $this->api->parseCustomerNetDays($p21Customer);
            if ($ccPaymentRequired || ! $netDays) {
                return false;
            }

            // Merchants may or may not want to validate credit status.
            $checkCreditStatus = $this->scopeConfig->getValue('payment/invoicepayment/validate_credit_status');
            $creditStatus = $this->api->parseCustomerCreditStatus($p21Customer);
            if ($checkCreditStatus && $creditStatus == 'COD') {
                return false;
            }

            // Merchants may or may not want to validate credit limit.
            $checkCreditLimit = $this->scopeConfig->getValue('payment/invoicepayment/validate_credit_limit');
            $creditLimit = $this->api->parseCustomerCreditLimit($p21Customer);
            $creditUsed = $this->api->parseCustomerCreditUsed($p21Customer);
            if ($checkCreditLimit && ($creditUsed + $quote->getGrandTotal()) >= $creditLimit) {
                return false;
            }
        }

        return parent::isAvailable($quote);
    }
}
