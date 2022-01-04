<?php
namespace Ripen\CatsyIntegration\Model;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Ripen\PimIntegration\Logger\Logger;
use Ripen\CatsyIntegration\Model\Config;

class Api
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $baseQuery;

    const ACTIONS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
    ];

    const DEFAULT_CONNECT_TIMEOUT = 0; // no limit

    /**
     * Api constructor.
     * @param Config $moduleConfig
     * @param Logger $logger
     */
    public function __construct(
        Config $moduleConfig,
        Logger $logger
    ) {
        $this->config = $moduleConfig;
        $this->logger = $logger;

        $this->client = new Client(
            [
                'base_uri' => $this->config->getBaseUrl(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config->getApiKey()
                ]
            ]
        );

        $this->baseQuery = [];
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
     * @throws GuzzleException
     * @throws \RuntimeException
     */
    public function makeHttpRequest($method, $path, $query = [], $body = null)
    {
        $method = strtolower($method);
        $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;

        if (!empty($query['connect_timeout'])) {
            $connectTimeout = $query['connect_timeout'];
            unset($query['connect_timeout']);
        }

        $data = [
            'query' => array_merge($this->baseQuery, $query),
            'connect_timeout' => $connectTimeout,
        ];

        if (!is_null($body)) {
            $data['body'] = json_encode($body);
        }

        $data['headers'] = [
            'Content-Type'     => 'application/json',
        ];

        try {
            $response = $this->client->request($method, $this->config->getBasePath() . $path, $data);
        } catch (GuzzleException $e) {
            $this->logger->error($e->getMessage());

            // This try/catch has been added solely so that we can log the error. Existing code is built expecting
            // GuzzleException to be thrown past this method, so we continue to do so for now.
            // TODO: Rethink exception handling throughout stack to handle exceptions at appropriate layer.
            throw $e;
        }

        return $this->parseResponseBody($response->getBody());
    }

    /**
     * Parses Response body
     *
     * @throws \RuntimeException
     * @return mixed
     */
    protected function parseResponseBody($body)
    {
        $parsedResponse = \json_decode($body, true);
        // Test if return a valid JSON.
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException(json_last_error_msg());
        }
        return $parsedResponse;
    }

    /**
     * @param array $params
     * @param $data
     * @return array|mixed
     */
    public function getProducts($params = [], $data)
    {
        $response =  $this->makeHttpRequest('POST', "/items/filters", $params, $data);
        return !empty($response['items']) ? $response['items'] : [];
    }

    public function getChildren($params = [], $data)
    {
        $response =  $this->makeHttpRequest('POST', "/items/filters", $params, $data);
        return !empty($response['items']) ? $response['items'] : [];
    }

    /**
     * @param array $params
     * @return array|mixed
     */
    public function getAttributes($params = [])
    {
        $response =  $this->makeHttpRequest('GET', "/attributes", $params);
        return !empty($response['attributes']) ? $response['attributes'] : [];
    }

    /**
     * @param array $params
     * @return array|mixed
     */
    public function getCategories($params = [])
    {
        $response =  $this->makeHttpRequest('GET', "/categories/categoryTree", $params);
        return !empty($response['categories']) ? $response['categories'] : [];
    }

    /**
     * @param $categoryId
     * @param array $params
     * @return mixed
     */
    public function getCategory($categoryId, $params = [])
    {
        $response =  $this->makeHttpRequest('GET', "/categories/{$categoryId}", $params);
        return $response['category'];
    }
}
