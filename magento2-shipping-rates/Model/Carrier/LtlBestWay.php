<?php

namespace Ripen\ShippingRates\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

class LtlBestWay extends AbstractCarrier implements CarrierInterface
{
    /**
     * Note: do not use underscores '_' in shipping methods codes as Magento uses underscores to determine carrier_code
     * @link https://stackoverflow.com/a/42317801
     * @var string
     */
    protected $_code = 'ltlbestway';
    protected $_isFixed = true;
    private $rateResultFactory;
    private $rateMethodFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        array $data = []
    ) {

        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

    }

    public function collectRates(RateRequest $request) {

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->rateResultFactory->create();
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));
        $shippingCost = (float)$this->getConfigData('shipping_cost');
        $method->setPrice($shippingCost);
        $method->setCost($shippingCost);
        $result->append($method);
        return $result;
    }

    public function getAllowedMethods() {
        return [$this->_code => $this->getConfigData('name')];
    }
}
