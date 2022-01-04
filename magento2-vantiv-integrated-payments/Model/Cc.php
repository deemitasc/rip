<?php

namespace Ripen\VantivIntegratedPayments\Model;

use Magento\Payment\Model\InfoInterface;
use Ripen\VantivIntegratedPayments\Exception\AvsCheckFailedException;
use Ripen\VantivIntegratedPayments\Exception\PaymentAccountCreationFailedException;
use Ripen\VantivIntegratedPayments\Exception\TransactionAuthorizationFailedException;

/**
 * Class Cc
 *
 * As Magento\Payment\Model\Method\Cc is deprecated, this class is likely to break on Magento 2.4+.
 * Original approach highlighted at @link https://www.classyllama.com/blog/how-to-create-payment-method-magento-2
 */
class Cc extends \Magento\Payment\Model\Method\Cc
{
    // this needs to be lower-cased to match the path in core_config_data
    const CODE = 'ripen_vantivintegratedpayments';

    protected $_code = self::CODE;

    protected $_canAuthorize = true;
    protected $_canCapture = false;
    protected $_canSaveCc = false;

    protected $api;
    protected $checkoutSession;
    protected $apiLogger;
    protected $config;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ripen\VantivIntegratedPayments\Model\Api $api,
        \Ripen\VantivIntegratedPayments\Logger\Logger $apiLogger,
        \Ripen\VantivIntegratedPayments\Model\Config $moduleConfig,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $moduleList, $localeDate, null, null, $data);

        $this->api = $api;
        $this->checkoutSession = $checkoutSession;
        $this->apiLogger = $apiLogger;
        $this->config = $moduleConfig;
    }

    /**
     * Validate payment method information object
     *
     * @inheritDoc
     */
    public function validate()
    {
        try {
            return parent::validate();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logValidationError($this->getInfoInstance(), $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $this->logValidationError($this->getInfoInstance(), $e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__('Unknown error processing credit card.'));
        }
    }

    /**
     * Capture Payment
     *
     * TODO: complete and test this method
     *
     * @inheritDoc
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        throw new \LogicException('Vantiv capture not implemented.');

        /*
        // Run authorize transaction if not already authorized.
        if (is_null($payment->getParentTransactionId())) {
            $this->authorize($payment, $amount);
        }

        // Build array of payment data for API request.
        $request = [
            'capture_amount' => $amount,
        ];

        // Run sale capture transaction.
        $response = $this->api->makeCaptureRequest($request);

        // Mark payment as complete.
        $payment->setIsTransactionClosed(true);

        return $this;
        */
    }

    /**
     * Authorize a payment, or create Payment Account
     *
     * @inheritDoc
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $quote = $this->checkoutSession->getQuote();

        // CC pre-auth enabled
        if ($this->api->isApiCCAuthEnabled()) {

            // Run authorize transaction.
            $response = $this->api->makeAuthRequest(
                $payment->getCcNumber(),
                $payment->getCcExpMonth(),
                $payment->getCcExpYear(),
                $payment->getCcCid(),
                $amount,
                $payment->getOrderId(),
                '',
                '',
                $quote->getBillingAddress()
            );

            // Run AVS check
            if (!$this->api->isAvsCheckPassed($response)) {
                $this->logAVSFailedResponse($payment, $response);
                if ($this->config->isProductionMode()) {
                    throw new AvsCheckFailedException(__($this->api->getApiAvsFailedUserMessage()));
                } else {
                    $this->apiLogger->notice('Bypassing AVS/CVV failure since not supported by gateway in test mode.');
                }
            }

            // Check if payment has been successfully authorized.
            if (!$this->api->isTransactionApproved($response)) {
                throw new TransactionAuthorizationFailedException(__($this->api->getResponseMessage($response)));
            }

            $transactionId = $this->api->getTransactionId($response);
            if ($transactionId) {
                /**
                 * Successful auth request.
                 * Set the transaction id on the payment so the capture request knows auth has happened.
                 */
                // set table sales_payment_transaction:txn_id
                $payment->setTransactionId($transactionId);
                // set table sales_payment_transaction:parent_txn_id
                $payment->setParentTransactionId($transactionId);

                /**
                 * Set additional transaction data (goes in additional_information on sales_order_payment)
                 */
                $payment->setAdditionalInformation('ExpressResponseCode', (string)$this->api->getResponseCode($response));
                $payment->setAdditionalInformation('ExpressResponseMessage', (string)$this->api->getResponseMessage($response));
                $payment->setAdditionalInformation('ApprovalNumber', (string)$this->api->getApprovalNumber($response));
                $payment->setAdditionalInformation('ProcessorName', (string)$this->api->getProcessorName($response));
                $payment->setAdditionalInformation('CardBrand', (string)$this->api->getCardBrand($response));
                $payment->setAdditionalInformation('CardLogo', (string)$this->api->getCardLogo($response));
            } else {
                throw new TransactionAuthorizationFailedException(__('Could not retrieve transaction ID.'));
            }
        } else {
            // Run AVS check
            $avsCheckResponse = $this->api->makeAvsCheckRequest(
                $payment->getCcNumber(),
                $payment->getCcExpMonth(),
                $payment->getCcExpYear(),
                $payment->getCcCid(),
                $amount,
                $quote->getBillingAddress(),
                $payment->getOrderId()
            );

            if (!$this->api->isAvsCheckPassed($avsCheckResponse)) {
                $this->logAVSFailedResponse($payment, $avsCheckResponse);
                if ($this->config->isProductionMode()) {
                    throw new AvsCheckFailedException(__($this->api->getApiAvsFailedUserMessage()));
                } else {
                    $this->apiLogger->notice('Bypassing AVS/CVV failure since not supported by gateway in test mode.');
                }
            }

            $response = $this->api->createPaymentAccount(
                $payment->getCcNumber(),
                $payment->getCcExpMonth(),
                $payment->getCcExpYear(),
                $quote->getId()
            );

            // Check if payment account has been successfully created.
            if (!$this->api->isTransactionApproved($response)) {
                throw new PaymentAccountCreationFailedException(__($this->api->getResponseMessage($response)));
            }

            $paymentAccountId = $this->api->getPaymentAccountId($response);
            if ($paymentAccountId) {
                /**
                 * Set additional transaction data (goes in additional_information on sales_order_payment)
                 */
                $payment->setAdditionalInformation('PaymentAccountID', (string)$paymentAccountId);
                $payment->setAdditionalInformation('QuoteID', (string)$quote->getId());
                $payment->setAdditionalInformation('ExpressResponseCode', (string)$this->api->getResponseCode($response));
                $payment->setAdditionalInformation('ExpressResponseMessage', (string)$this->api->getResponseMessage($response));
                $payment->setAdditionalInformation('ServicesID', (string)$this->api->getServicesId($response));
            } else {
                throw new PaymentAccountCreationFailedException(__('Could not retrieve Payment Account ID.'));
            }
        }

        /**
         * Processing is not done yet. The capture method first checks to make sure an
         * authorize request has been successful.  Once authorization and capture happen,
         * the status of the payment is updated by setting setIsTransationClosed to true.
         *
         * So at this point we can't close this yet.
         */
        $payment->setIsTransactionClosed(false);

        return $this;
    }

    /**
     * Set the payment action to authorize_and_capture
     *
     * @inheritDoc
     */
    public function getConfigPaymentAction()
    {
        return self::ACTION_AUTHORIZE;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $message
     */
    protected function logValidationError(InfoInterface $payment, string $message)
    {
        $validationMessage = "Validation failed for card {$payment->getCcType()}-{$payment->getCcLast4()} with message: $message";
        $this->apiLogger->notice($validationMessage);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $avsCheckResponse
     */
    protected function logAVSFailedResponse(InfoInterface $payment, $avsCheckResponse)
    {
        // log the CC info that failed the check
        $avsLogMessage = "AVS Check failed to pass for card {$payment->getCcType()}-{$payment->getCcLast4()}.";
        $avsResponseCode = $this->api->getAvsResponseCode($avsCheckResponse);
        $cvvResponseCode = $this->api->getCvvResponseCode($avsCheckResponse);
        $responseMessage = $this->api->getResponseMessage($avsCheckResponse);

        $avsLogMessage .= (! empty($avsResponseCode)) ? " AVS Response Code: {$avsResponseCode}." : " No AVS Response Code returned.";
        $avsLogMessage .= (! empty($cvvResponseCode)) ? " CVV Response Code: {$cvvResponseCode}." : " No CVV Response Code returned.";
        $avsLogMessage .= (! empty($responseMessage)) ? " Message: {$responseMessage}" : "";

        $this->apiLogger->notice($avsLogMessage);
    }
}
