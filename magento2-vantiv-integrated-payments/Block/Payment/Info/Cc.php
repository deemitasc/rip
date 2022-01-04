<?php
/**
 * Overrides Magento\Payment\Block\Info\Cc
 */

namespace Ripen\VantivIntegratedPayments\Block\Payment\Info;


class Cc extends \Magento\Payment\Block\Info\Cc
{
    /**
     * Add additional information to the data array
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);

        // Vantiv Certification requires ApprovalNumber from original Authorization transaction be displayed
        $approvalNumber = $this->getInfo()->getAdditionalInformation('ApprovalNumber');
        if (!empty($approvalNumber)) {
            $data[(string)__('Authorization Code')] = $approvalNumber;

            return $transport->addData($data);
        }

        return $transport;
    }
}
