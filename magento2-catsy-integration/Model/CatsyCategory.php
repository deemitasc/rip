<?php
namespace Ripen\CatsyIntegration\Model;

use GuzzleHttp\Exception\GuzzleException;
use Ripen\PimIntegration\Logger\Logger;

class CatsyCategory implements \Ripen\PimIntegration\Model\PimCategoryInterface
{
    protected $categoriesTree;
    /**
     * @var Api
     */
    protected $api;
    /**
     * @var DataParser
     */
    protected $dataParser;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * PimCategory constructor.
     * @param \Ripen\PimIntegration\Model\Api $api
     * @param DataParser $dataParser
     * @param Logger $logger
     */
    public function __construct(
        Api $api,
        DataParser $dataParser,
        Logger $logger
    ) {
        $this->api = $api;
        $this->dataParser = $dataParser;
        $this->logger = $logger;
    }

    /**
     * @return array|mixed
     */
    public function getCategoriesTree(){

        if (!$this->categoriesTree) {
            try {
                $this->categoriesTree = $this->api->getCategories();
            } catch (GuzzleException $e) {
                $this->logger->error("API failed when getting category tree - " . $e->getMessage());
            }
        }

        return $this->categoriesTree;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getByName($name)
    {
        $pimCategory = null;
        $categories = $this->getCategoriesTree();
        try{
            foreach($categories as $category) {
                if ($this->dataParser->parseCategoryName($category) == $name){
                    $pimCategory =  $category;
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Getting pim category by name failed: " . $e->getMessage());
        }

        return $pimCategory;
    }

    /**
     * @param $id
     * @param bool $childIdsOnly
     * @return mixed
     */
    public function getById($id, $childIdsOnly = true)
    {
        try {
            $pimCategory = $this->api->getCategory($id, ['childIdsOnly'=>$childIdsOnly]);
        } catch (GuzzleException $e) {
            $this->logger->error("API failed when getting category by ID - " . $e->getMessage());
        }
        return $pimCategory;
    }

    /**
     * @param $pimCategory
     * @return mixed
     */
    public function getId($pimCategory)
    {
        return $this->dataParser->parseCategoryId($pimCategory);
    }
}
