<?php
namespace Ripen\ShippingRates\Plugin;
use Magento\Store\Model\ScopeInterface;

class UpdateUpsRates
{

    const UPS_GROUND_METHOD_CODE = '03';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * UpdateUpsRates constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Ups\Model\Carrier $subject
     * @param $result
     * @return mixed
     */
    public function afterGetResult(\Magento\Ups\Model\Carrier $subject, $result)
    {
        $newResult = clone $result;

        $rates = $result->getAllRates();
        if (is_array($rates)){
            $newResult->reset();
            foreach($rates as $rate) {
                if ($rate->getCarrier() == 'ups'){
                    // Reset price to 0
                    $rate->setPrice(0);
                }
                $newResult->append($rate);
            }
        }

        return $newResult;
    }

    /**
     * @return mixed
     */
    public function getRateIncrease()
    {
        return $this->scopeConfig->getValue('carriers/ups/rate_increase', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getUpsGroundFlatPrice()
    {
        return $this->scopeConfig->getValue('carriers/ups/ground_flat_price', ScopeInterface::SCOPE_STORE);
    }
}
