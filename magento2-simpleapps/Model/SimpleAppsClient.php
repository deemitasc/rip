<?php
namespace Ripen\SimpleApps\Model;

use GuzzleHttp\Client as GuzzleClient;

class SimpleAppsClient extends GuzzleClient
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $moduleConfig
     */
    public function __construct(
        Config $moduleConfig
    ) {
        $this->config = $moduleConfig;

        parent::__construct(
            [
                'base_uri' => $this->config->getBaseUrl(),
                'headers' => [
                    'x-api-key' => $this->config->getXApiKey()
                ]
            ]
        );
    }
}
