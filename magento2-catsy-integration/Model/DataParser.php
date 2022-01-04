<?php
namespace Ripen\CatsyIntegration\Model;

use GuzzleHttp\Client;
use function Aws\boolean_value;

class DataParser implements \Ripen\PimIntegration\Model\DataParserInterface
{
    const IMAGE_ASSET_TYPE = 'IMAGE';
    const PDF_ASSET_TYPE = 'PDF';
    const SPECIAL_PARSING_RULES = [
        'product_introduction_statement' => 'parseDescription'
    ];

    /**
     * @var Config
     */
    protected $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getImageAssetType()
    {
        return self::IMAGE_ASSET_TYPE;
    }

    /**
     * @return string
     */
    public function getPdfAssetType()
    {
        return self::PDF_ASSET_TYPE;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseMainImage($data){
        return $data['main_image'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseSecondaryImage($data){
        return $data['secondary_image'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseAssetId($data){
        return  $data['id'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseAssetLargeUrl($data){
        return  $data['large_url'];
    }


    /**
     * @param $data
     * @return mixed
     */
    public function parseAssetOriginalUrl($data){
        return $data['url'];
    }


    /**
     * @param $data
     * @return mixed
     */
    public function parsePdfUrl($data){
        return $data['url'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseAttributeCode($data)
    {
        return $data['key'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseAttributeType($data)
    {
        return $data['dataType'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseAttributeName($data)
    {
        return $data['name'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseProductName($data)
    {
        return $data['product_name'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseProductType($data)
    {
        if($this->isParentProduct($data))
            $type = 'variable';
        else if ($this->isSimpleProduct($data)){
            $type = 'simple';
        } else {
            $type = 'variant';
        }

        return $type;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseProductGroupName($data)
    {
        return $data['product_group_name'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseUpdatedDate($data)
    {
        return $data['update_date'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseProductGroupSku($data)
    {
        return $data['product_group_sku'];
    }



    /**
     * @param $data
     * @return array|mixed
     */
    public function parseProductVariants($data)
    {
        return !empty($data['variants']) ? $data['variants'] : [];
    }

    /**
     * @param $data
     * @return array|mixed
     */
    public function parseOptionAttributeKeys($data)
    {
        return !empty($data['catsy$option_attribute_keys']) ? $data['catsy$option_attribute_keys'] : [];
    }

    /**
     * @param $magentoAttributeCode
     * @return string|string[]
     */
    public function getAttributeCode($magentoAttributeCode){
        return str_replace($this->config->getAttributePrefix(), '', $magentoAttributeCode);
    }

    /**
     * @param $data
     * @return bool
     */
    public function parseAttributeIsSearchable($data)
    {
        return false;
    }

    /**
     * @param $data
     * @return bool
     */
    public function parseAttributeIsFilterable($data)
    {
        return !empty($data['searchFacet']) && $data['searchFacet'] ? true : false;
    }

    /**
     * @param $data
     * @return bool|mixed
     */
    public function parseAttributeIsVisibleOnFront($data)
    {
        return true;
    }

    /**
     * @param $data
     * @return array|mixed
     */
    public function parseAttributeSelectValues($data)
    {
        return !empty($data['picklistValues']) ? $data['picklistValues'] : [];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseAttributeGroup($data)
    {
        return $data['picklistValues'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseCategoryId($data){
        return $data['id'];
    }

    /**
     * @param $data
     * @return mixed
     */
    public function parseCategoryName($data){
        return $data['name'];
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseCategoryParentId($data){
        return !empty($data['parent_id']) ? $data['parent_id'] : null;
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseCategoryPosition($data){
        return !empty($data['seq_order']) ? $data['seq_order'] : null;
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseCategoryLevel($data){
        return !empty($data['level']) ? $data['level'] : null;
    }

    /**
     * @param $data
     * @return array
     */
    public function parseProductCategoryNames($data){
        $categoryNames = [];
        // TODO: Make this category field list configurable.
        $categoryFields = [
            'category',
            'category_2',
            'category_3',
            'category_4',
            'category_5',
            'custom_category',
            'my_custom_safety_products'
        ];
        foreach($categoryFields as $fieldName) {
            if (!empty($data[$fieldName])){
                $categories = explode('>', $data[$fieldName]);
                $category = end($categories);
                $categoryNames[] = trim($category);
            }
        }
        return $categoryNames;
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseProductAssets($data){
        return !empty($data['assets']) ? $data['assets'] : [];
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseAssetType($data){
        return !empty($data['asset_type']) ? $data['asset_type'] : null;
    }

    /**
     * @param $asset
     * @return bool
     */
    public function isAssetImage($asset){
        return $this->parseAssetType($asset) == $this->getImageAssetType();
    }

    /**
     * @param $asset
     * @return bool
     */
    public function isAssetPdf($asset){
        return $this->parseAssetType($asset) == $this->getPdfAssetType();
    }

    /**
     * @param $data
     * @param $code
     * @return mixed|string
     */
    public function parseAttributeData($data, $code){
        $parsedValue = $data[$code];

        if(in_array($code, array_keys(self::SPECIAL_PARSING_RULES))){
            $action = self::SPECIAL_PARSING_RULES[$code];
            if(is_callable(array($this, $action))){
                $parsedValue = $this->$action($data);
            }
        }
        return $parsedValue;
    }

    /**
     * @param $data
     * @return string
     */
    public function parseDescription($data){

        $description = "";
        if (!empty($data['product_introduction_statement']))
            $description .=  $data['product_introduction_statement'].' ';

        if (!empty($data['product_feature_statement']))
            $description .=  $data['product_feature_statement'].' ';

        if (!empty($data['product_application_statement']))
            $description .=  $data['product_application_statement'].' ';

        return trim($description);
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function isSimpleProduct($data){

        // Simple product has no parent_item_id OR
        // it has parent_item_id set BUT it should match item_id AND has 0 or exactly 1 variant
        $isSimple = (
            empty($data['parent_item_id']) ||
            ( $data['parent_item_id'] == $data['item_id'] && count($this->parseProductVariants($data)) <= 1 )
        );

        return $isSimple;
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function isParentProduct($data){
        return !empty($data['child_item_count']) && intval($data['child_item_count']) > 1 && $data['parent_item_id'] == $data['item_id'];
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function isChildProduct($data){
        return $data['parent_item_id'] && $data['parent_item_id'] != $data['item_id'];
    }

}
