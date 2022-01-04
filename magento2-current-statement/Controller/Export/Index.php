<?php
namespace Ripen\CurrentStatement\Controller\Export;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $customerUrl;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Ripen\CurrentStatement\Helper\CurrentStatement
     */
    protected $statementHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Ripen\CurrentStatement\Helper\CurrentStatement $currentStatementHelper
    ) {
        $this->fileSystem = $fileSystem;
        $this->customerSession = $customerSession;
        $this->customerUrl = $customerUrl;
        $this->scopeConfig = $scopeConfig;
        $this->statementHelper= $currentStatementHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $this->messageManager->addErrorMessage("Please Log In.");
            $redirectUrl = $this->customerUrl->getLoginUrl();
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($redirectUrl);
            return $resultRedirect;
        }

        $filePath = $this->getCurrentStatements();
        return $this->downloadCsv($filePath);
    }

    public function downloadCsv($file)
    {
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }
    }

    public function getCurrentStatements()
    {
        $heading = [
            'Invoice Amount',
            'Invoice Date',
            'Invoice Number',
            'ERP Order Number',
            'Web Order Number',
            'Paid In Full?',
            'Invoice Open Amount',
            'Due Date',
            'PO Number',
            'Terms Due Date'
        ];

        $tmpDir = $this->fileSystem->getDirectoryWrite(\Magento\Framework\Filesystem\DirectoryList::SYS_TMP);
        $outputFile = $tmpDir->getAbsolutePath() . "currentstatement_" . date('Ymd_His') . ".csv";

        $handle = fopen($outputFile, 'w');

        fputcsv($handle, $heading);

        $currentStatements = $this->statementHelper->getInvoiceList($this->statementHelper->getFilterParams());

        foreach ($currentStatements as $currentStatement) {
            $data = [
                  $this->statementHelper->formatAccountingAmount($currentStatement['total_amount']),
                  date('m/d/Y', strtotime($currentStatement['invoice_date'])),
                  $currentStatement['invoice_no'],
                  $currentStatement['order_no'],
                  $currentStatement['increment_id'],
                  $currentStatement['paid_in_full_flag'],
                  $this->statementHelper->formatAccountingAmount($this->statementHelper->getOpenAmount($currentStatement)),
                  date('m/d/Y', strtotime($currentStatement['net_due_date'])),
                  $currentStatement['po_no'],
                  date('m/d/Y', strtotime($currentStatement['terms_due_date']))
            ];
            fputcsv($handle, $data);
        }

        fclose($handle);

        return $outputFile;
    }
}
