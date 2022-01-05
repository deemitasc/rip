<?php
namespace Ripen\SimpleApps\Model;

use LengthException;
use LogicException;
use RuntimeException;
use GuzzleHttp\Exception\GuzzleException;
use Ripen\Prophet21\Exception\P21ApiException;

class Api
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SimpleAppsClient\Proxy
     */
    protected $client;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    const ACTIONS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
    ];

    const DEFAULT_CONNECT_TIMEOUT = 0; // no limit

    /**
     * @param Config $moduleConfig
     * @param SimpleAppsClient\Proxy $simpleAppsClient
     * @param \Ripen\Prophet21\Logger\Logger $logger
     */
    public function __construct(
        \Ripen\SimpleApps\Model\Config $moduleConfig,
        \Ripen\SimpleApps\Model\SimpleAppsClient\Proxy $simpleAppsClient,
        \Ripen\Prophet21\Logger\Logger $logger
    ) {
        $this->config = $moduleConfig;
        $this->client = $simpleAppsClient;
        $this->logger = $logger;
    }

    /**
     * -----------------------------------------------
     * API Calls
     * -----------------------------------------------
     */

    /**
     * @param string $method
     * @param string $path
     * @param array $query
     * @param mixed $body
     * @return mixed
     * @throws P21ApiException
     */
    public function makeHttpRequest($method, $path, $query = [], $body = null)
    {
        $baseQuery = [
            'siteid' => $this->config->getSiteID()
        ];

        $method = strtoupper($method);
        $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;

        if (!empty($query['connect_timeout'])) {
            $connectTimeout = $query['connect_timeout'];
            unset($query['connect_timeout']);
        }

        $data = [
            'query' => array_merge($baseQuery, $query),
            'connect_timeout' => $connectTimeout,
        ];

        if (!is_null($body)) {
            $data['body'] = json_encode($body);
        }

        if ($this->config->isDebugModeActive()) {
            $queryString = json_encode($query);
            $this->logger->debug("SimpleApps $method API call to $path with query $queryString and body: " . json_encode($body));
        }

        try {
            $response = $this->client->request($method, $path, $data);
        } catch (GuzzleException $e) {
            $queryString = json_encode($query);
            $this->logger->error("HTTP error from SimpleApps API on call to $path with query $queryString: {$e->getMessage()}");
            throw new P21ApiException('Error calling SimpleApps API.');
        }

        if ($this->config->isDebugModeActive()) {
            $this->logger->debug("SimpleApps $method API response from $path: " . $response->getBody());
        }

        try {
            return $this->parseResponseBody($response->getBody());
        } catch (RuntimeException $e) {
            $queryString = json_encode($query);
            $this->logger->error("Error parsing SimpleApps API response on call to $path with query $queryString: {$e->getMessage()}");
            throw new P21ApiException('Error calling SimpleApps API.');
        }
    }

    /**
     * Overloading method to handle the majority of the expected endpoint calls to the API that do not have a specific
     * method defined in this class
     * 
     * TODO: Identify all uses of this method, convert to regular methods, and then remove this handler.
     *
     * @deprecated
     * @param string $method
     * @param array $args
     * @return array
     * @throws LogicException
     * @throws P21ApiException
     */
    public function __call($method, $args = [])
    {
        $this->logger->debug("SimpleApps deprecated magic handler invoked via $method(). Convert to standard method.");

        /**
         * Handle calls to the api by interpreting the function call itself.
         *
         * For example, getWhoAmI will be split into 'get' for the method/action and 'WhoAmI' for the path
         */
        list($action, $path) = preg_split('/(?=[A-Z])/', $method, 2);
        if (empty($action) || empty($path)) {
            throw new LogicException("$method is not defined");
        }

        $action = strtoupper($action);

        if (!in_array($action, self::ACTIONS)) {
            throw new LogicException("$action is not a valid action.");
        }

        // handle params
        $params = [];
        if (!empty($args)) {
            $params = $args[0];
        }

        // handle the immediate :id part of the api endpoint, if present
        if (!empty($params['id'])) {
            $path .= '/' . $params['id'];
            unset($params['id']);
        }

        return $this->makeHttpRequest($action, '/ecommerce/' . strtolower($path), $params);

    }

    /**
     * @param int $limit
     * @param int $offset
     * @param bool $deletedFlag
     * @param bool $onlineOnlyFlag
     * @param array $resourceList
     * @param array $itemList
     * @param string $modifiedSince
     * @return array
     * @throws P21ApiException
     */
    public function getProducts(
        $limit = null,
        $offset = null,
        $deletedFlag = false,
        $onlineOnlyFlag = true,
        $resourceList = null,
        $itemList = null,
        $modifiedSince = null
    ) {
        if ($resourceList) {
            $params['resource_list'] = implode(',', $resourceList);
        }

        if ($limit) {
            $params['limit'] = $limit;
        }

        if ($offset) {
            $params['offset'] = $offset;
        }

        // TODO: Consider removing this argument; not actually used anywhere because not currently reliable.
        //
        // Only reason to keep would be if it works in conjunction with $modifiedSince to get recently
        // deleted products, in which case we can use it to make `SanitizeDeletedProducts` cleaner. This
        // will likely depend on switching to using `inv_mast_uid` to identify products and correctly track
        // product SKU changes without deleting/recreating the products. See ENG-101.
        //
        // Also note that the API parameter name is misleading. It is called "only deleted," implying that
        // it is restricting the default set which would include deleted and active products. In fact, if
        // omitted or set to 0, it will return only active products.
        //
        // If we do keep, then consider creating a new, separate method called getDeletedProducts() rather than
        // passing this as an argument here. Both this method and the new getDeletedProducts() could then call
        // a protected function to assemble their common API parameters.
        $params['only_deleted'] = intval($deletedFlag);

        if ($modifiedSince) {
            $params['modified_since'] = $modifiedSince;
        }

        if ($itemList) {
            $params['item_list'] = $itemList;

            // This tells SimpleApps to match the item list based on the item ID only, rather than also matching
            // on product alternate codes. This is appropriate at least until ENG-101 would be addressed
            // and we can treat items that change item IDs (SKUSs) reliably as the same product. It may be
            // appropriate even after that, but that will need to be re-evaluated then.
            $params['skip_altcode'] = 1;
        }

        // Online check tells SimpleApps to return only products with a class 5 value that indicates the
        // product is "online." However, these class 5 values are semi-hard-coded in SimpleApps for each client.
        // Since we can't see or control that filter, it is not ideal to use. See ENG-103 for future plans.
        $params['online_check'] = intval($onlineOnlyFlag);

        $params['get_counts'] = 0;

        $products = $this->makeHttpRequest('GET', 'ecommerce/items', $params);

        return $products;
    }

    /**
     * @param string $id
     * @param array $params
     * @return array
     * @throws P21ApiException
     */
    public function getItemStock($id, $params = [])
    {
        $response = $this->makeHttpRequest('GET', '/ecommerce/items/' . urlencode($id) . '/stock', $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * @param string $id
     * @param array $params
     * @return int
     * @throws P21ApiException
     */
    public function getItemNetStock($id, $params = [])
    {
        $response = $this->getItemStock($id, $params);
        return $this->calculateNetStock($response);
    }

    /**
     * @param string $id
     * @return array
     * @throws P21ApiException
     */
    public function getItemImages($id, $params = [])
    {
        $response = $this->api->makeHttpRequest('GET', "ecommerce/items/" . urlencode($id) . "/links", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws P21ApiException
     */
    public function getAllImages($limit = null, $offset = null)
    {
        if ($limit) {
            $params['limit'] = $limit;
        }

        if ($offset) {
            $params['offset'] = $offset;
        }

        // Note, this api call returns data sorted by modification_date
        $response = $this->api->makeHttpRequest('GET', "ecommerce/items/all/links", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * This api call requires customer_id parameter
     *
     * @param array $params
     * @return array
     * @throws P21ApiException
     */
    public function getP21Orders($params = [])
    {
        $response =  $this->makeHttpRequest('GET', "/ecommerce/orders", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * This api call requires customer_id parameter
     *
     * @param $order_no
     * @param array $params
     * @return array|null
     * @throws P21ApiException
     */
    public function getP21Order($order_no, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/orders/{$order_no}", $params);
        return !empty($response['data']) ? $response['data'] : null;
    }

    /**
     * @param $invoice_no
     * @param array $params
     * @return array|null
     * @throws P21ApiException
     */
    public function getP21Invoice($invoice_no, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/invoices/{$invoice_no}", $params);
        return !empty($response['data']) ? $response['data'] : null;
    }

    /**
     * @param $params
     * @return array
     * @throws P21ApiException
     */
    public function getP21Invoices($params)
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/invoices", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     *
     * @param $order_no
     * @param array $params
     * @return array
     * @throws P21ApiException
     */
    public function getOrderLineItems($order_no, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/orders/{$order_no}/lines", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * @param $invoice_no
     * @param array $params
     * @return array
     * @throws P21ApiException
     */
    public function getInvoiceLineItems($invoice_no, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/invoices/{$invoice_no}/lines", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * @param $invoice_no
     * @param $params
     * @return array|null
     * @throws P21ApiException
     */
    public function getInvoice($invoice_no, $params)
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/invoices/{$invoice_no}", $params);
        return !empty($response['data']) ? $response['data'] : null;
    }

    /**
     * Returns all quotes.
     * This api call requires customer_id parameter
     *
     * @param array $params
     * @return array
     * @throws P21ApiException
     */
    public function getP21Quotes($params = [])
    {
        $response =  $this->makeHttpRequest('GET', "/ecommerce/quotes", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * Returns quote by quote number.
     * This api call requires customer_id parameter
     *
     * @param $quote_no
     * @param array $params
     * @return array
     * @throws P21ApiException
     */
    public function getP21Quote($quote_no, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/quotes/{$quote_no}", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * Returns quote lines by quote number.
     * This api call requires customer_id parameter
     *
     * @param $quote_no
     * @param array $params
     * @return array
     * @throws P21ApiException
     */
    public function getQuoteLineItems($quote_no, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/quotes/{$quote_no}/lines", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * @param $web_reference_no
     * @param array $params
     * @return mixed
     * @throws P21ApiException
     */
    public function getWebOrder($web_reference_no, $params = [])
    {
        return $this->makeHttpRequest('GET', "/ecommerce/weborders/{$web_reference_no}", $params);
    }

    /**
     * @param string $magentoOrderId
     * @param array $params
     * @return mixed
     * @throws P21ApiException
     */
    public function getWebOrdersByMagentoOrderId(string $magentoOrderId, $params = [])
    {
        $params = array_merge($params, ['other_data' => $magentoOrderId]);
        return $this->makeHttpRequest('GET', "/ecommerce/weborders", $params);
    }

    /**
     * @param string $poNumber
     * @param array $params
     * @return mixed
     * @throws P21ApiException
     */
    public function getWebOrdersByPONumber(string $poNumber, $params = [])
    {
        $params = array_merge($params, ['po_no' => $poNumber]);
        return $this->makeHttpRequest('GET', "/ecommerce/weborders", $params);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws P21ApiException
     */
    public function getWebOrderNotYetImported($params = [])
    {
        $params = array_merge($params, [
            'imported' => 'no',
            // Set a high limit to bypass default limit of 10. Note that 0 seems to work to bypass the
            // limit, but SimpleApps could not confirm this as an intended feature to be relied on.
            'limit' => 1000000
        ]);
        return $this->makeHttpRequest('GET', "/ecommerce/weborders", $params);
    }

    /**
     * @param $order_no
     * @param array $params
     * @return mixed
     * @throws P21ApiException
     */
    public function getPickTicketStubs($order_no, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/orders/{$order_no}/picktickets", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * @param string $order_no
     * @param string $pick_ticket_no
     * @param array $params
     * @return array
     * @throws P21ApiException
     */
    public function getPickTicket($order_no, $pick_ticket_no, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/orders/{$order_no}/picktickets/{$pick_ticket_no}", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * @param string $order_no
     * @param string $pick_ticket_no
     * @param array $params
     * @return array
     * @throws P21ApiException
     */
    public function getPickTicketLineItems($order_no, $pick_ticket_no, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/orders/{$order_no}/picktickets/{$pick_ticket_no}/details", $params);
        return !empty($response['data']) ? $response['data'] : [];
    }

    /**
     * @param $carrierId
     * @param array $params
     * @return string|null
     * @throws P21ApiException
     */
    public function getCarrierName($carrierId, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/shippingmethods/{$carrierId}", $params);
        return $response['data']['name'] ?? null;
    }

    /**
     * @param array $params
     * @return array
     * @throws P21ApiException
     */
    public function getCarriers($params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/shippingmethods", $params);
        return array_column($response['data'] ?? [], 'name', 'id');
    }

    /**
     * @param $id
     * @param array $params
     * @return array|null
     * @throws P21ApiException
     */
    public function getSalesRepCustomers($id, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/salesreps/{$id}/customers", $params);
        return !empty($response['data']) ? $response['data'] : null;
    }

    public function getCustomerContacts($id, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/customers/{$id}/contacts", $params);
        return !empty($response['data']) ? $response['data'] : null;
    }

    /**
     * @param $id
     * @param array $params
     * @return array|null
     * @throws P21ApiException
     */
    public function getContactById($id, $params = [])
    {
        $response = $this->makeHttpRequest('GET', "/ecommerce/contacts/{$id}", $params);
        return !empty($response['data']) ? $response['data'] : null;
    }

    /**
     * @param array $data
     * @param array $params
     * @return int|array
     * @throws P21ApiException
     */
    public function postWebOrder($data, $params = [])
    {
        $response = $this->makeHttpRequest('POST', '/ecommerce/weborders', $params, $data);

        return $this->parseWebOrderUID($response);
    }

    /**
     * @param int $p21CustomerId
     * @param array $skus
     * @param array $quantities
     * @return array
     * @throws \LengthException
     */
    public function getCustomerPriceData($p21CustomerId, $skus, $quantities = null)
    {
        if (is_array($quantities) && count($quantities) !== count($skus)) {
            throw new \LengthException('Mismatch between SKUs and quantities specified.');
        }

        $itemsQuery = [];
        foreach ($skus as $i => $sku) {
            // Setting uom to null should cause a fallback to default_selling_unit, as desired. It can't be
            // omitted entirely, though, or the API will return an error.
            // https://basecamp.com/2805226/projects/16452113/todos/408813517
            $itemsQuery[] = [
                'item_id' => $sku,
                'qty' => $quantities[$i] ?? 1,
                'uom' => null
            ];
        }

        $query = [
            'customer_id' => (int) $p21CustomerId,
            'use_price_family' => (bool) $quantities,
            'items' => $itemsQuery
        ];

        $prices = $this->makeHttpRequest('POST', 'ecommerce/prices', [], $query);

        if (empty($prices['data'])) {
            return [];
        }

        return array_column($prices['data']['items'], null, 'item_id');
    }

    /**
     * @param $customerId
     * @param array $params
     * @return array
     * @throws P21ApiException
     */
    public function getCustomerShipTos($customerId, $params = [])
    {
        $response = $this->makeHttpRequest('GET', '/ecommerce/customers/' . $customerId . '/shiptos', $params);
        $return = [];

        if (! empty($response['data'])) {
            foreach ($response['data'] as $row) {
                $return[] = $this->normalizeAddress($row);
            }
        }

        return $return;
    }

    /**
     * @param $customerId
     * @param array $params
     * @return array|null
     * @throws P21ApiException
     */
    public function getCustomer($customerId, $params = [])
    {
        $response = $this->makeHttpRequest('GET', '/ecommerce/customers/' . $customerId, $params);
        return !empty($response['data']) ? $response['data'] : null;
    }

    /**
     * @param $customerId
     * @param array $params
     * @return bool
     * @throws P21ApiException
     */
    public function getCustomerPoNumberFlag($customerId, $params = [])
    {
        return $this->parseCustomerPoRequiredFlag($this->getCustomer($customerId, $params));
    }

    /**
     * @param $customerId
     * @param array $params
     * @return string
     * @throws P21ApiException
     */
    public function getCustomerName($customerId, $params = [])
    {
        return $this->parseCustomerName($this->getCustomer($customerId, $params));
    }

    /**
     * @param $customerId
     * @param array $params
     * @return bool
     * @throws P21ApiException
     */
    public function getCustomerTaxableFlag($customerId, $params = [])
    {
        return $this->parseCustomerTaxableFlag($this->getCustomer($customerId, $params));
    }

    /**
     * -----------------------------------------------
     * Auxiliary methods for interpreting API responses
     * -----------------------------------------------
     */

    /**
     * @param $order
     * @return mixed
     */
    public function parseOrderNo($order)
    {
        return $order['order_no'];
    }

    /**
     * @param $invoice
     * @return mixed
     */
    public function parseInvoiceNo($invoice)
    {
        return $invoice['invoice_no'];
    }

    /**
     * @param $invoice
     * @return int
     */
    public function parsePaidInFull($invoice)
    {
        return $invoice['paid_in_full_flag'] == 'N' ? false : true;
    }

    /**
     * @param $invoiceDetails
     * @return mixed
     */
    public function parseInvoiceShippingAmount($invoiceDetails)
    {
        return $invoiceDetails['shipping_cost'];
    }

    /**
     * @param $invoiceDetails
     * @return mixed
     */
    public function parseInvoiceSubtotal($invoiceDetails)
    {
        return $invoiceDetails['total_amount'];
    }

    /**
     * @param $invoiceDetails
     * @return mixed
     */
    public function parseInvoiceGrandTotal($invoiceDetails)
    {
        $shippingCost = !empty($invoiceDetails['shipping_cost ']) ? $invoiceDetails['shipping_cost '] : 0;
        return $invoiceDetails['total_amount'] + $shippingCost;
    }

    /**
     * @param array $pickTicket
     * @return string|null
     */
    public function parsePickTicketCarrierId($pickTicket)
    {
        return $pickTicket['carrier_id'];
    }

    /**
     * @param array $pickTicket
     * @return string|null
     */
    public function parsePickTicketTracking($pickTicket)
    {
        return
            $pickTicket['tracking_no']
            ?? $pickTicket['clippership_tracking_no']
            ?? $pickTicket['ups_tracking_no']  // ups_tracking_no is for p21 worldship integration
            ?? null;
    }

    /**
     * @param $pickTicket
     * @return mixed
     */
    public function parsePickTicketInvoiceNumber($pickTicket)
    {
        $data = $pickTicket['data'] ?? $pickTicket;
        return $data['invoice_no'];
    }

    /**
     * @param array $pickTicket Accepts a full pick ticket or pick ticket stub
     * @return string|null
     */
    public function parsePickTicketNumber($pickTicket)
    {
        $data = $pickTicket['data'] ?? $pickTicket;
        return $data['pick_ticket_no'];
    }

    /**
     * @param $response
     * @return mixed
     */
    public function parseP21OrderNumber($response)
    {
        if (isset($response['data']['meta']['order_no'])) {
            return $response['data']['meta']['order_no'];
        }
        return null;
    }

    public function parseP21OrderCancelFlag($response)
    {
        if (isset($response['data']['cancel_flag'])) {
            return $response['data']['cancel_flag'] == 'Y' ? true : false;
        }
        return false;
    }

    /**
     * @param $response
     * @return mixed
     */
    public function parsePONumber($response)
    {
        if (isset($response['data']['customer_po_number'])) {
            return $response['data']['customer_po_number'];
        }
        return null;
    }

    /**
     * @param $response
     * @return mixed
     */
    public function parseWebOrderMetaData($response)
    {
        if (isset($response['data']['meta'])) {
            return $response['data']['meta'];
        }
        return false;
    }

    /**
     * @param $response
     * @return bool
     */
    public function isOrderShipped($response)
    {
        return !empty($response['data']['meta']['shipped']);
    }

    /**
     * @param $response
     * @return bool
     */
    public function isOrderApproved($response)
    {
        return !empty($response['data']['meta']['approved']);
    }

    /**
     * @param $response
     * @return bool
     */
    public function isOrderCompleted($response)
    {
        return !empty($response['data']['meta']['completed']);
    }

    /**
     * @param $stockLocations
     * @return int
     */
    public function calculateNetStock($stockLocations)
    {
        $netStock = 0;
        foreach ($stockLocations as $location) {
            $netStock += $this->calculateLocationNetStock($location);
        }
        return $netStock;
    }

    /**
     * @param array $location
     * @return int
     */
    public function calculateLocationNetStock(array $location)
    {
        $return = 0;

        $return += !empty($location['qty_on_hand']) ? $location['qty_on_hand'] : 0;
        $return -= !empty($location['qty_allocated']) ? $location['qty_allocated'] : 0;
        $return -= !empty($location['qty_quarantined']) ? $location['qty_quarantined'] : 0;

        return $return;
    }

    /**
     * @param array $response
     * @return int
     */
    public function parseWebOrderUID($response)
    {
        if (isset($response['data'])) {
            if (isset($response['data']['header'])) {
                if (isset($response['data']['header']['web_orders_uid'])) {
                    return $response['data']['header']['web_orders_uid'];
                }
            }
        }
        return 0;
    }

    /**
     * Function to make sure address array data is consistent in terms of keys/formatting, and limits the scope of
     * changes should the format from the API ever change down the road.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeAddress(array $data)
    {
        return [
            'address_id' => $data['id'],
            'customer_code' => $data['corp_address_id'],
            'name' => $data['name'],
            'street1' => $data['phys_address1'] ?: $data['mail_address1'],
            'street2' => $data['phys_address2'] ?: $data['mail_address2'],
            'city' => ucwords(strtolower($data['phys_city'] ?: $data['mail_city'])),
            'region' => $data['phys_state'] ?: $data['mail_state'],
            'postcode' => $data['phys_postal_code'] ?: $data['mail_postal_code'],
            'country_id' => $data['phys_country'] ?: $data['mail_country'],
            'telephone' => $data['central_phone_number'],
            'email' => $data['email_address'],
            'default_packing_basis' => $data['packing_basis'],
            'default_carrier_id' => $data['default_carrier_id'],
        ];
    }

    /**
     * Parses Response body
     *
     * @throws RuntimeException
     * @return mixed
     */
    protected function parseResponseBody($body)
    {
        $parsedResponse = \json_decode($body, true);
        // Test if return a valid JSON.
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(json_last_error_msg());
        }
        return $parsedResponse;
    }

    /**
     * @param $lineItem
     * @return mixed
     */
    public function parseLineItemQty($lineItem)
    {
        return $lineItem['qty_ordered'];
    }

    /**
     * @param $lineItem
     * @return mixed
     */
    public function parseLineItemSku($lineItem)
    {
        return $lineItem['item_id'];
    }

    public function parseInvoiceLineItemUniqueId($lineItem)
    {
        return $lineItem['invoice_line_uid'];
    }

    /**
     * @param $lineItem
     * @return mixed
     */
    public function parseLineItemPrice($lineItem)
    {
        return $lineItem['unit_price'];
    }

    public function parseLineItemShipQty($lineItem)
    {
        return $lineItem['ship_quantity'];
    }

    /**
     * @param $lineItem
     * @return mixed
     */
    public function parseLineItemQtyShipped($lineItem)
    {
        return $lineItem['qty_shipped'];
    }

    /**
     * @param $lineItem
     * @return mixed
     */
    public function parseLineItemQtyCanceled($lineItem)
    {
        return $lineItem['qty_canceled'];
    }

    /**
     * @param $lineItem
     * @return mixed
     */
    public function parseOrderLineItemUniqueId($lineItem)
    {
        return $lineItem['oe_line_uid'];
    }

    /**
     * @param $lineItem
     * @return float
     */
    public function parseOrderLineItemTaxAmount($lineItem)
    {
        return $lineItem['tax_item'] == 'Y' ? $lineItem['sales_tax'] : 0;
    }

    public function parseInvoiceLineItemTaxAmount($lineItem)
    {
        //TODO: Must change. Invoice line items don;t have tax amount provided in data
        return $lineItem['tax_item'] == 'N' ? 0 : $lineItem['sales_tax'];
    }

    /**
     * @param $lineItem
     * @return mixed
     */
    public function parseLineItemExtendedPrice($lineItem)
    {
        return $lineItem['extended_price'];
    }

    public function parseInvoiceFreight($data)
    {
        return $data['freight'];
    }

    public function parseInvoiceTaxAmount($data)
    {
        return $data['tax_amount'];
    }

    public function parseOrderLineItemQty($data)
    {
        return $data['qty'];
    }

    public function parseInvoiceAmountPaid($data)
    {
        return $data['amount_paid'];
    }

    /**
     * @param $id
     * @param array $params
     * @return array
     */
    public function getSalesRepCustomersArray($id, $params = [])
    {
        $return = [];
        $customers = $this->getSalesRepCustomers($id, $params);

        foreach ($customers as $customer) {

            // normalize the return data here to avoid having to update the array keys elsewhere, should the api data structure ever change
            $customerRow = [
                'customerCode' => isset($customer['customer_id']) ? $customer['customer_id'] : null,
                'company' => isset($customer['name']) ? $customer['name'] : null,
                'street' => isset($customer['address1']) ? $customer['address1'] : null,
                'suffix' => isset($customer['address2']) ? $customer['address2'] : null,
                'city' => isset($customer['city']) ? $customer['city'] : null,
                'region' => isset($customer['state']) ? $customer['state'] : null,
                'postCode' => isset($customer['postal_code']) ? $customer['postal_code'] : null,
            ];

            $return[] = $customerRow;
        }

        return $return;
    }

    /**
     * @param array $response
     * @return bool
     */
    public function parseCustomerPoRequiredFlag($response)
    {
        if (isset($response['po_no_required'])) {
            return $response['po_no_required'] == 'Y' ? true : false;
        }
        return false;
    }

    /**
     * @param $response
     * @return bool
     */
    public function parseCustomerTaxableFlag($response)
    {
        if (isset($response['taxable_flag'])) {
            return $response['taxable_flag'] == 'Y' ? true : false;
        }
        return false;
    }

    /**
     * @param $response
     * @return string|null
     */
    public function parseCustomerName($response)
    {
        return $response['customer_name'] ?? null;
    }


    /**
     * @param $data
     * @return mixed|null
     */
    public function parseCustomerCreditStatus($data)
    {
        return isset($data['credit_status']) ? $data['credit_status'] : null;
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseCustomerCreditLimit($data)
    {
        return isset($data['credit_limit']) ? $data['credit_limit'] : null;
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseCustomerCreditUsed($data)
    {
        return isset($data['credit_limit_used']) ? $data['credit_limit_used'] : null;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseCustomerContactEmailAddress($data)
    {
        return $data['email_address'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseCustomerContactFirstName($data)
    {
        return $data['first_name'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseCustomerContactLastName($data)
    {
        return $data['last_name'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseCustomerContactId($data)
    {
        return $data['id'];
    }

    /**
     * @param $data
     * @return bool
     */
    public function parseCustomerRequiredPaymentUponRelease($data)
    {
        return (($data['req_pymt_upon_release_of_items'] ?? null) == 'Y');
    }

    /**
     * @param $data
     * @return int
     */
    public function parseCustomerNetDays($data)
    {
        return isset($data['net_days']) ? intval($data['net_days']) : 0;
    }

    /**
     * @param $response
     * @return array
     */
    public function parseWebOrderUidsAsArray($response)
    {
        $return = [];

        if (! empty($response['data'])) {
            foreach ($response['data'] as $data) {
                if (isset($data['web_orders_uid'])) {
                    $return[] = $data['web_orders_uid'];
                }
            }
        }

        return $return;
    }
}
