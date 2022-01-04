<?php
namespace Ripen\PayMyBill\Model;
class PaymybillLog extends \Magento\Framework\Model\AbstractModel
{
    public function _construct(){
        $this->_init("Ripen\PayMyBill\Model\ResourceModel\PaymybillLog");
    }
}
