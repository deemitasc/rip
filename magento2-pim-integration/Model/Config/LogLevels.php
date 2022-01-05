<?php
namespace Ripen\PimIntegration\Model\Config;

use Ripen\PimIntegration\Logger\Logger;

class LogLevels implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $levels = array_keys(Logger::getLevels());
        return array_map(function ($level) {
            return ['value' => $level, 'label' => $level];
        }, $levels);
    }
}
