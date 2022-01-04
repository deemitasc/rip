<?php
namespace Ripen\CurrentStatement\Block;

class CurrentStatement extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    /**
     * @var \Ripen\CurrentStatement\Helper\CurrentStatement
     */
    protected $statementHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Ripen\CurrentStatement\Helper\CurrentStatement $statementHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->storeManager = $storeManager;
        $this->priceHelper = $priceHelper;
        $this->statementHelper = $statementHelper;
    }

    public function getInvoices()
    {
        return $this->statementHelper->getInvoiceList($this->statementHelper->getFilterParams());
    }

    /**
     * Get invoice totals
     *
     * @param $openInvoices
     * @return array
     */
    public function getTotals($openInvoices)
    {
        $current = 0;
        $pastDue = 0;
        $pastDue30 = 0;
        $pastDue60 = 0;

        foreach ($openInvoices as $openInvoice) {
            $amountDue = $openInvoice['total_amount'] - $openInvoice['amount_paid'];

            if ($openInvoice['terms_due_date'] >= date('Y-m-d H:i:s')) {
                $current += $amountDue;
            } else {
                if (
                    date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime('+ 0 days', strtotime($openInvoice['terms_due_date']))) &&
                    date('Y-m-d H:i:s') <= date('Y-m-d H:i:s', strtotime('+30 days', strtotime($openInvoice['terms_due_date'])))
                ) {
                    $pastDue += $amountDue;
                }
                if (
                    date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime('+30 days', strtotime($openInvoice['terms_due_date']))) &&
                    date('Y-m-d H:i:s') <= date('Y-m-d H:i:s', strtotime('+60 days', strtotime($openInvoice['terms_due_date'])))
                ) {
                    $pastDue30 += $amountDue;
                }
                if (date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime('+60 days', strtotime($openInvoice['terms_due_date'])))) {
                    $pastDue60 += $amountDue;
                }
            }
        }

        $totalDue = $current + $pastDue + $pastDue30 + $pastDue60;

        return [
            'current' => $current,
            'past_due' => $pastDue,
            'past_due_30' => $pastDue30,
            'past_due_60' => $pastDue60,
            'total_due' => $totalDue,
        ];
    }

    /**
     * @param $invoiceNumber
     * @return string
     */
    public function getInvoiceUrl($invoiceNumber)
    {
        $url = $this->getUrl('currentstatement/view/invoice', ['invoice_no' => $invoiceNumber]);
        return $url;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getExportUrl()
    {
        $paramString = pathinfo($this->_urlBuilder->getCurrentUrl(), PHP_URL_QUERY);

        return $this->storeManager->getStore()->getBaseUrl() . 'currentstatement/export/index' . $paramString;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreAddress()
    {
        return $this->storeManager->getStore()->getFormattedAddress();
    }
}
