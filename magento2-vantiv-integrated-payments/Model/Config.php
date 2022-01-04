<?php

namespace Ripen\VantivIntegratedPayments\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isProductionMode()
    {
        return $this->scopeConfig->isSetFlag('payment/ripen_vantivintegratedpayments/api_prod_mode', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->isProductionMode()
            ? 'https://transaction.elementexpress.com'
            : 'https://certtransaction.elementexpress.com';
    }

    /**
     * @return string
     */
    public function getBaseServiceUrl()
    {
        return $this->isProductionMode()
            ? 'https://services.elementexpress.com'
            : 'https://certservices.elementexpress.com';
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->scopeConfig->getValue('payment/ripen_vantivintegratedpayments/company_name', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getAccountID()
    {
        return $this->scopeConfig->getValue('payment/ripen_vantivintegratedpayments/api_account_id', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getAccountToken()
    {
        return $this->scopeConfig->getValue('payment/ripen_vantivintegratedpayments/api_account_token', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getAcceptorID()
    {
        return $this->scopeConfig->getValue('payment/ripen_vantivintegratedpayments/api_acceptor_id', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getTerminalID()
    {
        return $this->scopeConfig->getValue('payment/ripen_vantivintegratedpayments/api_terminal_id', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getCertificationActiveStatus()
    {
        return $this->scopeConfig->isSetFlag('payment/ripen_vantivintegratedpayments/certification_active', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getApiCreditCardAuthorizationModeActiveStatus()
    {
        return $this->scopeConfig->isSetFlag('payment/ripen_vantivintegratedpayments/api_cc_auth_mode_active', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getApiAvsCheckPassedResponseCodes()
    {
        return (string)strtoupper($this->scopeConfig->getValue('payment/ripen_vantivintegratedpayments/api_avs_code_whitelist', ScopeInterface::SCOPE_STORE));
    }

    /**
     * @return string
     */
    public function getApiCvvCheckPassedResponseCodes()
    {
        return (string)strtoupper($this->scopeConfig->getValue('payment/ripen_vantivintegratedpayments/api_cvv_code_whitelist', ScopeInterface::SCOPE_STORE));
    }

    /**
     * @return string
     */
    public function getApiAvsFailedUserMessage()
    {
        return $this->scopeConfig->getValue('payment/ripen_vantivintegratedpayments/api_avs_failed_user_message', ScopeInterface::SCOPE_STORE);
    }
}
