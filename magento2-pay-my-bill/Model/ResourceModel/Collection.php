<?php
namespace Ripen\PayMyBill\Model\ResourceModel;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    public function _construct(){
        $this->_init("Ripen\PayMyBill\Model\PaymybillLog","Ripen\PayMyBill\Model\ResourceModel\PaymybillLog");
    }
}
