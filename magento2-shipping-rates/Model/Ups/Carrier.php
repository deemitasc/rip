<?php
namespace Ripen\ShippingRates\Model\Ups;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\AsyncClientInterface;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Xml\Security;
use Magento\Shipping\Model\Rate\Result\ProxyDeferredFactory;
use Magento\Ups\Helper\Config;

class Carrier extends \Magento\Ups\Model\Carrier
{
    /**
     * Copied parent constructor and moved $data parameter position to the end to get around an issue where a plugin on
     * \Magento\Shipping\Model\Carrier\AbstractCarrier would cause an exception during checkout:
     *
     * "Exception #0 (BadMethodCallException): Missing required argument $data of Ripen\ShippingRates\Model\Ups\Carrier."
     *
     * With the \Magento\Ups\Model\Carrier parent constructor, the $data argument is in the middle of the constructor list.
     * It's supposed to be optional, but PHP doesn't allow optional arguments followed by required arguments
     * (even nullable required arguments); they effectively become required that point. It's stupid that PHP ever
     * allowed that in the first place. at least they have finally deprecated that in PHP 8, but that doesn't help us here.
     *
     * The changing position works because the object manager that does dependency injection works based on argument
     * names, rather than argument position. The difference from the parent shouldn't be a problem, but it will allow
     * $data to truly be an optional argument, bypassing the issue.
     *
     * Speculation of the cause of the issue is that, by putting a plugin on the class, it causes the class to get
     * instantiated just slightly differently than if the plugin is not there, and in the case of the plugin being
     * present, the generated class the plugin causes fails to supply the $data argument.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param Config $configHelper
     * @param ClientFactory $httpClientFactory
     * @param ProxyDeferredFactory|null $proxyDeferredFactory
     * @param AsyncClientInterface|null $asyncHttpClient
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger, Security $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        Config $configHelper, ClientFactory $httpClientFactory,
        ?ProxyDeferredFactory $proxyDeferredFactory,
        ?AsyncClientInterface $asyncHttpClient = null,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $xmlSecurity, $xmlElFactory, $rateFactory, $rateMethodFactory, $trackFactory, $trackErrorFactory, $trackStatusFactory, $regionFactory, $countryFactory, $currencyFactory, $directoryData, $stockRegistry, $localeFormat, $configHelper, $httpClientFactory, $data, $asyncHttpClient, $proxyDeferredFactory);
    }


    public function getResult()
    {
        if ($this->_scopeConfig->getValue('carriers/ups/override_ups_rates_to_zero', ScopeInterface::SCOPE_STORE)){
            $newResult = clone $this->_result;

            $rates = $this->_result->getAllRates();
            if (is_array($rates)){
                $newResult->reset();
                foreach($rates as $rate) {
                    $rate->setPrice(0);
                    $newResult->append($rate);
                }
            }
            return $newResult;
        }

        return $this->_result;

    }

    protected function _getQuotes()
    {
        if ($this->_scopeConfig->getValue('carriers/ups/override_ups_rates_to_zero', ScopeInterface::SCOPE_STORE)){
            $result = $this->_rateFactory->create();
            $allowedMethods = explode(",", $this->getConfigData('allowed_methods'));

            foreach ( $allowedMethods as $method ) {
                $rate = $this->_rateMethodFactory->create();
                $rate->setCarrier('ups');
                $rate->setCarrierTitle($this->getConfigData('title'));
                $rate->setMethod($method);
                $methodArr = $this->getShipmentByCode($method);
                $rate->setMethodTitle($methodArr);
                $rate->setCost(0);
                $rate->setPrice(0);
                $result->append($rate);
            }

            return $result;
        }

        return parent::_getQuotes();
    }

}
