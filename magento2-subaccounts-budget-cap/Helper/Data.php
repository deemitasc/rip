<?php

namespace Ripen\BudgetCap\Helper;

use DateTime;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const BUDGET_CAP_PERIODS = [
        'week'=> 'per calendar week',
        'month'=> 'per calendar month',
        'quarter'=> 'per calendar quarter',
        'year'=> 'per calendar year'
    ];

    public function __construct(
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    public function getBudgetCapPeriods(){
        return self::BUDGET_CAP_PERIODS;
    }

    function getFirstDayOf($period)
    {
        $validPeriods = array_keys(self::BUDGET_CAP_PERIODS);

        if ( ! in_array($period, $validPeriods))
            throw new InvalidArgumentException('Invalid period. Must be one of: ' . implode(', ', $validPeriods));

        $date = new DateTime();

        switch ($period) {
            case 'year':
                $date->modify('first day of january ' . $date->format('Y'));
                break;
            case 'quarter':
                $month = $date->format('n') ;

                if ($month < 4) {
                    $date->modify('first day of january ' . $date->format('Y'));
                } elseif ($month > 3 && $month < 7) {
                    $date->modify('first day of april ' . $date->format('Y'));
                } elseif ($month > 6 && $month < 10) {
                    $date->modify('first day of july ' . $date->format('Y'));
                } elseif ($month > 9) {
                    $date->modify('first day of october ' . $date->format('Y'));
                }
                break;
            case 'month':
                $date->modify('first day of this month');
                break;
            case 'week':
                $date->modify(($date->format('w') === '0') ? 'monday last week' : 'monday this week');
                break;
        }

        return $date->format('Y-m-d H:i:s');
    }

    public function getTotalSpentPerPeriod($customerId, $period){

        $today = date("Y-m-d H:i:s", time());
        $startDate = $this->getFirstDayOf($period);

        $orderTotals = $this->orderCollectionFactory->create($customerId)
            ->addAttributeToSelect('grand_total')
            ->addFieldToFilter('created_at', array('from'=>$startDate, 'to'=>$today))
            ->getColumnValues('grand_total');

        $grandTotalSum = array_sum($orderTotals);

        return $grandTotalSum;
    }

}
