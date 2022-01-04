<?php
namespace Ripen\PayMyBill\Block;

class Payment extends \Magento\Framework\View\Element\Template
{
    /**
     * Payment constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function isPaymentLinkEnabled(){
        return $this->scopeConfig->getValue('paymybill/general/enable_paymybill');
    }
}
