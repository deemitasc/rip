<?php

namespace Ripen\BudgetCap\Block;

use Magento\Customer\Model\Session as CustomerSession;
use Ripen\BudgetCap\Helper\Data as RipenHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface as CurrencyInterface;


class BudgetInfo extends \Magento\Framework\View\Element\Template
{

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var \Ripen\BudgetCap\Helper\Data
     */
    private $helper;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        CustomerSession $customerSession,
        RipenHelper $helper,
        CurrencyInterface $currency,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->currency = $currency;

        parent::__construct($context, $data);

    }

    public function getTotalBudgetCap()
    {
        $totalBudget = 0;
        $subaccountTransportDataObject = $this->customerSession->getSubaccountData();
        if($subaccountTransportDataObject){
            $totalBudget = $this->currency->format($subaccountTransportDataObject->getAdditionalInformationValue('manage_order_budget_cap'));
        }
        return $totalBudget;
    }

    public function getBudgetCapPeriod()
    {
        $period = '';
        $subaccountTransportDataObject = $this->customerSession->getSubaccountData();
        if($subaccountTransportDataObject){
            $period = $subaccountTransportDataObject->getAdditionalInformationValue('manage_order_budget_cap_period');
        }
        return $period;
    }

    public function getRemainingBudget()
    {
        $remainingBudget = 0;
        $subaccountTransportDataObject = $this->customerSession->getSubaccountData();
        if($subaccountTransportDataObject){

            $subAccountBudgetCap = (float) $subaccountTransportDataObject
                ->getAdditionalInformationValue('manage_order_budget_cap');
            $subAccountBudgetCapPeriod = $subaccountTransportDataObject
                ->getAdditionalInformationValue('manage_order_budget_cap_period');

            if( $subAccountBudgetCap > 0){
                $previouslySpentPerPeriod = $this->helper->getTotalSpentPerPeriod($subaccountTransportDataObject->getCustomerId(), $subAccountBudgetCapPeriod);
                $remainingBudget = $this->currency->format($subAccountBudgetCap - $previouslySpentPerPeriod);
            }
        }

        return $remainingBudget;
    }

}
