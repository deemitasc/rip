<?php

namespace Ripen\BudgetCap\Observer\Checkout\Quote;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Ripen\BudgetCap\Helper\Data as RipenHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface as CurrencyInterface;

class SubmitBefore implements ObserverInterface
{

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var \Ripen\BudgetCap\Helper\Data
     */
    private $helper;

    /**
     * SubmitBefore constructor.
     * @param CustomerSession $customerSession
     * @param \Ripen\BudgetCap\Helper\Data $helper
     */
    public function __construct(
        CustomerSession $customerSession,
        RipenHelper $helper,
        CurrencyInterface $currency
    ) {
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->currency = $currency;
    }

    /**
     * Quote submit before event handler.
     *
     * @param Observer $observer Observer object.
     *
     * @return SubmitBefore
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $subaccountTransportDataObject = $this->customerSession->getSubaccountData();

        if($subaccountTransportDataObject){
            $quote = $observer->getQuote();

            $subAccountBudgetCap = (float) $subaccountTransportDataObject
                ->getAdditionalInformationValue('manage_order_budget_cap');
            $subAccountBudgetCapPeriod = $subaccountTransportDataObject
                ->getAdditionalInformationValue('manage_order_budget_cap_period');

            if( $subAccountBudgetCap > 0){
                $previouslySpentPerPeriod = $this->helper->getTotalSpentPerPeriod($subaccountTransportDataObject->getCustomerId(), $subAccountBudgetCapPeriod);
                if ( $previouslySpentPerPeriod + $quote->getSubtotal() > $subAccountBudgetCap ) {
                    throw new LocalizedException(
                        __("This order would exceed your budget allocation of ". $this->currency->format($subAccountBudgetCap, false) ." for this ".$subAccountBudgetCapPeriod.".")
                    );
                }
            }
        }

        return $this;
    }
}
