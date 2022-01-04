<?php
namespace Ripen\CurrentStatement\Helper;

class CurrentStatement extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * API call to get invoices always limits the response with 10 items. "limit" parameter must be passed to
     * override this limit. We set this parameter to a random high number to get all orders for a specific customer.
     */
    const INVOICE_IMPORT_LIMIT = 100000;

    /**
     * TODO: Eliminate this, as it should not exist on a singleton helper. (Memoize the value some other way.)
     *
     * @var int $customerCode
     */
    public $customerCode;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    /**
     * @var \Ripen\SimpleApps\Model\Api
     */
    protected $api;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    const FILTER_PAYMENT_PARAM = 'paid';
    const FILTER_FROM_DATE_PARAM = 'from_date';
    const FILTER_TO_DATE_PARAM = 'to_date';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Ripen\SimpleApps\Model\Api $api,
        \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->customerSession = $customerSession;
        $this->customerFactory = $customerFactory;
        $this->priceHelper = $priceHelper;
        $this->api = $api;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->orderRepository = $orderRepository;
        $this->timezone = $timezone;
        $this->productRepository = $productRepository;

        parent::__construct($context);
    }

    /**
     * Get a list of open invoices
     * @return array
     */
    public function getInvoiceList($args = [])
    {
        $params['customer_id'] = $this->getCustomerCode();
        $params['limit'] = self::INVOICE_IMPORT_LIMIT;
        $params['detailed'] = 1; // include all fields to avoid an extra API call
        $params = array_merge($params, $args);

        if (!empty($args['fromDate'])) {
            $params['invoice_date_begin'] = $this->timezone->convertConfigTimeToUtc($args['fromDate'] . ' 00:00:00');
        }
        if (!empty($args['toDate'])) {
            $params['invoice_date_end'] = $this->timezone->convertConfigTimeToUtc($args['toDate'] . ' 23:59:59');
        }

        $invoices = $this->api->getP21Invoices($params);

        $p21OrderIds = [];

        foreach ($invoices as $invoice) {
            $p21OrderIds[] = $invoice['order_no'];
        }

        if (!empty($p21OrderIds)) {
            $orders = $this->getOrderIncrementIds($p21OrderIds);

            foreach ($invoices as $key => $invoice) {
                $invoice['increment_id'] = $orders[$invoice['order_no']] ?? '';
                $invoices[$key] = $invoice;
            }
        }
        return $invoices;
    }

    /**
     * Format currency amount to match accounting style.
     *
     * @param float $amount
     * @return string
     */
    public function formatAccountingAmount($amount)
    {
        if ($amount < 0) {
            $amount = "(" . $this->priceHelper->currency(-1 * $amount, true, false) . ")";
        } else {
            $amount = $this->priceHelper->currency($amount, true, false);
        }
        return $amount;
    }

    /**
     * @param array $invoice
     * @return float
     */
    public function getOpenAmount($invoice)
    {
        return $invoice['total_amount'] - $invoice['amount_paid'];
    }

    /**
     * @return mixed
     */
    public function getCustomerCode()
    {
        if (!$this->customerCode) {
            $customer = $this->customerFactory->create()->load($this->customerSession->getCustomerId());
            $this->customerCode = $customer->getErpCustomerId();
        }

        return $this->customerCode;
    }

    /**
     * @param $invoiceNumber
     * @return null
     * @throws \Ripen\Prophet21\Exception\P21ApiException
     */
    public function getInvoice($invoiceNumber)
    {
        $params['customer_id'] = $this->getCustomerCode();
        $invoice = $this->api->getP21Invoice($invoiceNumber, $params);
        return $invoice;
    }

    /**
     * Get order details by invoice number
     * @param $invoice
     * @return array|mixed
     * @throws \Ripen\Prophet21\Exception\P21ApiException
     */
    public function getOrder($invoice)
    {
        $order = [];
        $params['customer_id'] = $this->getCustomerCode();
        if ($invoice['order_no']) {
            $order = $this->api->getP21Order($invoice['order_no'], $params);
        }
        return $order;
    }

    /**
     * Get pick ticket for an invoice
     *
     * @param $invoice
     * @return array
     * @throws \Ripen\Prophet21\Exception\P21ApiException
     */
    public function getPickTicket($invoice)
    {
        $params['customer_id'] = $this->getCustomerCode();

        if ($invoice['order_no']) {
            $pickTicketStubs = $this->api->getPickTicketStubs($invoice['order_no'], $params);

            foreach ($pickTicketStubs as $pickTicketStub) {
                if ($pickTicketStub['invoice_no'] == $invoice['invoice_no']) {
                    return $this->api->getPickTicket($invoice['order_no'], $pickTicketStub['pick_ticket_no'], $params);
                }
            }
        }

        return [];
    }

    /**
     * @param array $pickTicket
     * @return string|null
     */
    public function getPickTicketTracking($pickTicket)
    {
        return $this->api->parsePickTicketTracking($pickTicket);
    }
    
    /**
     * Get invoice line items
     *
     * @param $invoiceNo
     * @return array
     * @throws \Ripen\Prophet21\Exception\P21ApiException
     */
    public function getInvoiceLines($invoiceNo)
    {
        $params['customer_id'] = $this->getCustomerCode();
        $params['limit'] = self::INVOICE_IMPORT_LIMIT;

        return $this->addProductAttributes($this->api->getInvoiceLineItems($invoiceNo, $params));
    }

    /**
     * @return array
     */
    public function getFilterParams()
    {
        $params = [];
        $paymentFilter = $this->_getRequest()->getParam(self::FILTER_PAYMENT_PARAM);
        $fromDate = $this->_getRequest()->getParam(self::FILTER_FROM_DATE_PARAM);
        $toDate = $this->_getRequest()->getParam(self::FILTER_TO_DATE_PARAM);

        switch (strtolower($paymentFilter)) {
            case 'both':
                break;
            case 'y':
                $params['paid_in_full'] = 'Y';
                break;
            case 'n':
            default:
                $params['paid_in_full'] = 'N';
        }

        if (! empty($fromDate)) {
            $params['fromDate'] = $fromDate;
        }

        if (! empty($toDate)) {
            $params['toDate'] = $toDate;
        }

        return $params;
    }

    /**
     * @param string|int $erpOrderNumber
     * @return int|null
     */
    public function getOrderIncrementId($erpOrderNumber)
    {
        $result = $this->getOrderIncrementIds([$erpOrderNumber]);

        return $result[$erpOrderNumber] ?? null;
    }

    /**
     * Given an array of ERP order numbers, fetch the matching orders and their increment IDs
     *
     * @param array $erpOrderNumbers
     * @return array
     */
    protected function getOrderIncrementIds($erpOrderNumbers)
    {
        $results = [];
        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $searchCriteriaFilter = $searchCriteriaBuilder
            ->addFilter('p21_order_no', 0, 'neq')
            ->addFilter('p21_order_no', $erpOrderNumbers, 'in');

        /** @var \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $searchCriteriaFilter->create();

        $orders = $this->orderRepository->getList($searchCriteria)->getItems();

        foreach($orders as $order) {
            $results[$order->getData('p21_order_no')] = $order->getIncrementId();
        }

        return $results;
    }

    /**
     * @param $data
     * @return array
     */
    protected function addProductAttributes($data)
    {
        foreach ($data as $key => $line) {
            try {
                $product = $this->productRepository->get($line['item_id']);
                $data[$key]['p21_short_code'] = $product->getData('p21_short_code');
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $data[$key]['p21_short_code'] = '';
            }
        }
        return $data;
    }
}
