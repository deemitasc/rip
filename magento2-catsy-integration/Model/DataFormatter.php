<?php
namespace Ripen\CatsyIntegration\Model;

class DataFormatter extends \Ripen\PimIntegration\Model\DataFormatter
{
    const FIELDS_REQUIRED_FORMATTING = [
        'short_description' => 'formatDescription'
    ];

    /**
     * @param $value
     * @return string|string[]|null
     */
    protected function formatDescription($value){
        $description = preg_replace("/\r\n|\r|\n/", '<br/>', strip_tags($value));
        return $description;
    }
}
