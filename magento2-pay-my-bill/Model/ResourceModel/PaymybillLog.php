<?php
namespace Ripen\PayMyBill\Model\ResourceModel;
class PaymybillLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct(){
        $this->_init("paymybill_log","id");
    }
}
