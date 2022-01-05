<?php
namespace Ripen\PimIntegration\Model;

interface DataParserInterface
{
    /**
     * @return string
     */
    public function getImageAssetType();

    /**
     * @return string
     */
    public function getPdfAssetType();

    /**
     * @param $data
     * @return mixed
     */
    public function parseMainImage($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseSecondaryImage($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseAssetId($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseAssetLargeUrl($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseAssetOriginalUrl($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parsePdfUrl($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseAttributeCode($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseAttributeType($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseAttributeName($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseProductType($data);

    /**
     * @param $data
     * @return array|mixed
     */
    public function parseProductVariants($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseOptionAttributeKeys($data);

    /**
     * @param $magentoAttributeCode
     * @return string|string[]
     */
    public function getAttributeCode($magentoAttributeCode);

    /**
     * @param $data
     * @return bool
     */
    public function parseAttributeIsSearchable($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseAttributeIsFilterable($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseAttributeIsVisibleOnFront($data);

    /**
     * @param $data
     * @return array|mixed
     */
    public function parseAttributeSelectValues($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseAttributeGroup($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseCategoryId($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseCategoryName($data);

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseCategoryParentId($data);

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseCategoryPosition($data);

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseCategoryLevel($data);

    /**
     * @param $data
     * @return array
     */
    public function parseProductCategoryNames($data);

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseProductAssets($data);

    /**
     * @param $data
     * @return mixed|null
     */
    public function parseAssetType($data);

    /**
     * @param $asset
     * @return bool
     */
    public function isAssetImage($asset);

    /**
     * @param $asset
     * @return bool
     */
    public function isAssetPdf($asset);

    /**
     * @param $data
     * @param $code
     * @return mixed|string
     */
    public function parseAttributeData($data, $code);

    /**
     * @param $data
     * @return string
     */
    public function parseDescription($data);

    /**
     * @param $data
     * @return string
     */
    public function isSimpleProduct($data);

    /**
     * @param $data
     * @return string
     */
    public function isParentProduct($data);

    /**
     * @param $data
     * @return string
     */
    public function isChildProduct($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseProductGroupName($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseProductGroupSku($data);

    /**
     * @param $data
     * @return mixed
     */
    public function parseUpdatedDate($data);


}
