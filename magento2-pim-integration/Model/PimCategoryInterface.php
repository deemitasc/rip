<?php
namespace Ripen\PimIntegration\Model;

interface PimCategoryInterface
{
    public function getCategoriesTree();
    public function getByName($name);
    public function getById($id, $childIdsOnly = true);
    public function getId($pimCategory);
}
