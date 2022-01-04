<?php

namespace Ripen\VantivIntegratedPayments\Model;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Quote\Model\Quote\Address;
use Ripen\VantivIntegratedPayments\Exception\RequestFailedException;
use Ripen\VantivIntegratedPayments\Exception\SystemCreditCardReversalTriggeredException;
use Symfony\Component\Config\Util\Exception\InvalidXmlException;

class Api
{
    const APPLICATION_ID = 9807;
    const APPLICATION_NAME = 'Ripen_VantivIntegratedPayments';
    const APPLICATION_VERSION = '1.0.0';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * Vantiv codes that are required by the API
     */
    const PAYMENT_ACCOUNT_TYPE = 0; // Credit Card
    const PARTIAL_APPROVED_FLAG = 0; // Not supported
    const MARKET_CODE = 3; // ECommerce
    const CARD_PRESENT_CODE = 3; // Not present
    const CARD_HOLDER_PRESENT_CODE = 7; // ECommerce
    const CARD_INPUT_CODE = 4; // Manually Keyed
    const CVV_PRESENCE_CODE = 2; // Provided
    const TERMINAL_CAPABILITY_CODE = 5; // Key Entered
    const TERMINAL_ENVIRONMENT_CODE = 6; // ECommerce
    const MOTO_ECI_CODE = 5; // Secure Electronic Commerce
    const TERMINAL_TYPE = 2; // eCommerce
    const REVERSAL_TYPE_SYSTEM = 0;
    const REVERSAL_TYPE_FULL = 1;
    const REVERSAL_TYPE_PARTIAL = 2;
    protected $systemReversalResponseCodes = [
      '1001',
      '1002',
    ];

    /**
     * Constructor.
     * @param Config $moduleConfig
     * @param Curl $curl
     */
    public function __construct(
        Config $moduleConfig,
        Curl $curl
    ) {
        $this->config = $moduleConfig;

        $this->curl = $curl;
    }

    /**
     * -----------------------------------------------
     * API Calls
     * -----------------------------------------------
     */

    /**
     * @return \SimpleXMLElement
     * @throws RequestFailedException
     */
    public function healthCheck()
    {
        return $this->makeCurlCall($this->getHealthCheckXmlBody());
    }

    /**
     * @param $ccNumber
     * @param $ccExpMonth
     * @param $ccExpYear
     * @param $cvv
     * @param $amount
     * @param string $referenceNumber
     * @param string $ticketNumber
     * @param string $paymentAccountID
     * @param array|Address $address
     * @return \SimpleXMLElement
     * @throws RequestFailedException
     * @throws SystemCreditCardReversalTriggeredException
     */
    public function makeAuthRequest($ccNumber, $ccExpMonth, $ccExpYear, $cvv, $amount, $referenceNumber = '', $ticketNumber = '', $paymentAccountID = '', $address = null)
    {
        $addressArray = (! is_null($address) && $address instanceof Address) ? $this->getAddressArray($address) : (array)$address;
        $params = $this->getCreditCardAuthorizationXmlBody($ccNumber, $ccExpMonth, $ccExpYear, $cvv, $amount, $referenceNumber, $ticketNumber, $paymentAccountID, $addressArray);

        try {
            $response = $this->makeCurlCall($params);

            // Check response code to see if we need to make a System CreditCardReversal per Vantiv Documentation
            $responseCode = $this->getResponseObjectByKey($response, 'ExpressResponseCode');
            if (in_array($responseCode, $this->systemReversalResponseCodes)) {
                $this->makeSystemReversal($amount, $referenceNumber, $ticketNumber);

                throw new SystemCreditCardReversalTriggeredException(__('System CreditCardReversal triggered due to ExpressResponseCode ' . $responseCode));
            }

            return $response;
        } catch (RequestFailedException $e) {
            // in case of no response, make system CreditCardReversal per Vantiv Documentation
            $this->makeSystemReversal($amount, $referenceNumber, $ticketNumber);

            throw new SystemCreditCardReversalTriggeredException(__('System CreditCardReversal triggered due to RequestFailedException'));
        }
    }

    /**
     * @param $ccNumber
     * @param $ccExpMonth
     * @param $ccExpYear
     * @param $cvv
     * @param $amount
     * @param string $referenceNumber
     * @param string $ticketNumber
     * @param string $paymentAccountID
     * @param array|Address $address
     * @return \SimpleXMLElement
     * @throws RequestFailedException
     * @throws SystemCreditCardReversalTriggeredException
     */
    public function makeCaptureRequest($ccNumber, $ccExpMonth, $ccExpYear, $cvv, $amount, $referenceNumber = '', $ticketNumber = '', $paymentAccountID = '', $address = null)
    {
        $addressArray = (! is_null($address) && $address instanceof Address) ? $this->getAddressArray($address) : (array)$address;
        $params = $this->getCreditCardSaleXmlBody($ccNumber, $ccExpMonth, $ccExpYear, $cvv, $amount, $referenceNumber, $ticketNumber, $paymentAccountID, $addressArray);
        try {
            $response = $this->makeCurlCall($params);

            // Check response code to see if we need to make a System CreditCardReversal per Vantiv Documentation
            $responseCode = $this->getResponseObjectByKey($response, 'ExpressResponseCode');
            if (in_array($responseCode, $this->systemReversalResponseCodes)) {
                $this->makeSystemReversal($amount, $referenceNumber, $ticketNumber);

                throw new SystemCreditCardReversalTriggeredException(__('System CreditCardReversal triggered due to ExpressResponseCode ' . $responseCode));
            }

            return $response;
        } catch (RequestFailedException $e) {
            // in case of no response, make system CreditCardReversal per Vantiv Documentation
            $this->makeSystemReversal($amount, $referenceNumber, $ticketNumber);

            throw new SystemCreditCardReversalTriggeredException(__('System CreditCardReversal triggered due to RequestFailedException'));
        }
    }

    /**
     * @param $ccNumber
     * @param $ccExpMonth
     * @param $ccExpYear
     * @param $cvv
     * @param $amount
     * @param array|Address $address
     * @param string $referenceNumber
     * @return \SimpleXMLElement
     * @throws RequestFailedException
     */
    public function makeAvsCheckRequest($ccNumber, $ccExpMonth, $ccExpYear, $cvv, $amount, $address, $referenceNumber = '')
    {
        $addressArray = (! is_null($address) && $address instanceof Address) ? $this->getAddressArray($address) : (array)$address;
        $params = $this->getCreditCardAvsOnlyXmlBody($ccNumber, $ccExpMonth, $ccExpYear, $cvv, $amount, $referenceNumber, '', '', $addressArray);

        return $this->makeCurlCall($params);
    }

    /**
     * @param $transactionID
     * @param $amount
     * @param $referenceNumber
     * @param $ticketNumber
     * @return \SimpleXMLElement
     * @throws RequestFailedException
     */
    public function completeAuth($transactionID, $amount, $referenceNumber, $ticketNumber = '')
    {
        $params = $this->getCreditCardAuthorizationCompletionXmlBody($transactionID, $amount, $referenceNumber, $ticketNumber);

        return $this->makeCurlCall($params);
    }

    /**
     * @param $ccNumber
     * @param $ccExpMonth
     * @param $ccExpYear
     * @param $referenceNumber
     * @return \SimpleXMLElement
     * @throws RequestFailedException
     */
    public function createPaymentAccount($ccNumber, $ccExpMonth, $ccExpYear, $referenceNumber)
    {
        $params = $this->getPaymentAccountCreateXmlBody($ccNumber, $ccExpMonth, $ccExpYear, $referenceNumber);

        return $this->makeCurlCall($params, 'service');
    }

    /**
     * Please note that this method was not included in the Certification
     *
     * @param $transactionID
     * @param $amount
     * @param $referenceNumber
     * @param string $ticketNumber
     * @return \SimpleXMLElement
     * @throws RequestFailedException
     */
    public function makeReturn($transactionID, $amount, $referenceNumber, $ticketNumber = '')
    {
        $params = $this->getCreditCardReturnXmlBody($transactionID, $amount, $referenceNumber, $ticketNumber);

        return $this->makeCurlCall($params);
    }

    /**
     * @param $type
     * @param $transactionID
     * @param $amount
     * @param $salesTaxAmount
     * @param $referenceNumber
     * @param string $ticketNumber
     * @return \SimpleXMLElement
     * @throws RequestFailedException
     */
    public function makeReversal($type, $transactionID, $amount, $salesTaxAmount, $referenceNumber, $ticketNumber = '')
    {
        $params = $this->getCreditCardReversalXmlBody($type, $transactionID, $amount, $salesTaxAmount, $referenceNumber, $ticketNumber);

        return $this->makeCurlCall($params);
    }

    /**
     * Used to make system-based CreditCardReversal, which can take place if initial auth did not return a response, or
     * if it returned with ExpressResponseCode of 1001 or 1002
     *
     * @param $amount
     * @param $salesTaxAmount
     * @param $referenceNumber
     * @param string $ticketNumber
     * @return \SimpleXMLElement
     * @throws RequestFailedException
     */
    public function makeSystemReversal($amount, $salesTaxAmount, $referenceNumber, $ticketNumber = '')
    {
        $params = $this->getCreditCardReversalXmlBody('system', 0, $amount, $salesTaxAmount, $referenceNumber, $ticketNumber);

        return $this->makeCurlCall($params);
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getTransaction(\SimpleXMLElement $response)
    {
        return $this->getResponseObjectByKey($response, 'Transaction');
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getPaymentAccount(\SimpleXMLElement $response)
    {
        return $this->getResponseObjectByKey($response, 'PaymentAccount');
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getTransactionId(\SimpleXMLElement $response)
    {
        $transaction = $this->getTransaction($response);

        if (!is_null($transaction)) {
            return $this->getResponseObjectByKey($transaction, 'TransactionID');
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getServicesId(\SimpleXMLElement $response)
    {
        return $this->getResponseObjectByKey($response, 'ServicesID');
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getReferenceNumber(\SimpleXMLElement $response)
    {
        $transaction = $this->getTransaction($response);

        if (!is_null($transaction)) {
            return $this->getResponseObjectByKey($transaction, 'ReferenceNumber');
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getApprovalNumber(\SimpleXMLElement $response)
    {
        $transaction = $this->getTransaction($response);

        if (!is_null($transaction)) {
            return $this->getResponseObjectByKey($transaction, 'ApprovalNumber');
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getPaymentAccountId(\SimpleXMLElement $response)
    {
        $paymentAccount = $this->getPaymentAccount($response);

        if (!is_null($paymentAccount)) {
            return $this->getResponseObjectByKey($paymentAccount, 'PaymentAccountID');
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getPaymentAccountReferenceNumber(\SimpleXMLElement $response)
    {
        $paymentAccount = $this->getPaymentAccount($response);

        if (!is_null($paymentAccount)) {
            return $this->getResponseObjectByKey($paymentAccount, 'PaymentAccountReferenceNumber');
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getResponseMessage(\SimpleXMLElement $response)
    {
        return $this->getResponseObjectByKey($response, 'ExpressResponseMessage');
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getResponseCode(\SimpleXMLElement $response)
    {
        return $this->getResponseObjectByKey($response, 'ExpressResponseCode');
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getCard(\SimpleXMLElement $response)
    {
        return $this->getResponseObjectByKey($response, 'Card');
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getCardLogo(\SimpleXMLElement $response)
    {
        $card = $this->getCard($response);

        if (!is_null($card)) {
            return $this->getResponseObjectByKey($card, 'CardLogo');
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getMaskedCard(\SimpleXMLElement $response)
    {
        $card = $this->getCard($response);

        if (!is_null($card)) {
            return $this->getResponseObjectByKey($card, 'CardNumberMasked');
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getExpirationMonth(\SimpleXMLElement $response)
    {
        $card = $this->getCard($response);

        if (!is_null($card)) {
            return $this->getResponseObjectByKey($card, 'ExpirationMonth');
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getExpirationYear(\SimpleXMLElement $response)
    {
        $card = $this->getCard($response);

        if (!is_null($card)) {
            return $this->getResponseObjectByKey($card, 'ExpirationYear');
        }

        return null;
    }

    public function getApprovedAmount(\SimpleXMLElement $response)
    {
        $transaction = $this->getTransaction($response);

        if (!is_null($transaction)) {
            return $this->getResponseObjectByKey($transaction, 'ApprovedAmount');
        }

        return null;
    }


    /**
     * @param \SimpleXMLElement $response
     * @return null|String
     */
    public function getAvsResponseCode(\SimpleXMLElement $response)
    {
        $card = $this->getCard($response);

        if (!is_null($card)) {
            $responseCode = $this->getResponseObjectByKey($card, 'AVSResponseCode');
            if (!is_null($responseCode)) {
                return $responseCode->__toString();
            }
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|String
     */
    public function getCvvResponseCode(\SimpleXMLElement $response)
    {
        $card = $this->getCard($response);

        if (!is_null($card)) {
            $responseCode = $this->getResponseObjectByKey($card, 'CVVResponseCode');
            if (!is_null($responseCode)) {
                return $responseCode->__toString();
            }
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getCardBrand(\SimpleXMLElement $response)
    {
        return $this->getCardLogo($response);
    }

    /**
     * @param \SimpleXMLElement $response
     * @return null|\SimpleXMLElement
     */
    public function getProcessorName(\SimpleXMLElement $response)
    {
        $transaction = $this->getTransaction($response);

        if (!is_null($transaction)) {
            return $this->getResponseObjectByKey($transaction, 'ProcessorName');
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return bool
     */
    public function isTransactionApproved(\SimpleXMLElement $response)
    {
        if ($this->getResponseObjectByKey($response, 'ExpressResponseCode') == 0) {
            return true;
        }
        return false;
    }

    /**
     * @param \SimpleXMLElement $response
     * @return bool
     */
    public function isAvsCheckPassed(\SimpleXMLElement $response)
    {
        if (! $this->isTransactionApproved($response)) {
            return false;
        }

        // NOTE: We default response codes to _ as a way to allow for a whitelist of a non-response.

        // check avs response
        $avsResponseCode = $this->getAvsResponseCode($response) ?: '_';
        $avsWhiteList = $this->config->getApiAvsCheckPassedResponseCodes();
        $avsPassed = (strpos($avsWhiteList, $avsResponseCode) !== false);

        // check CVV response
        $cvvResponseCode = $this->getCvvResponseCode($response) ?: '_';
        $cvvWhiteList = $this->config->getApiCvvCheckPassedResponseCodes();
        $cvvPassed = (strpos($cvvWhiteList, $cvvResponseCode) !== false);

        return ($avsPassed && $cvvPassed);
    }

    /**
     * -----------------------------------------------
     * Helpers
     * -----------------------------------------------
     */

    /**
     * @param $params
     * @param $type
     * @return \SimpleXMLElement
     * @throws RequestFailedException
     */
    protected function makeCurlCall($params, $type = 'transaction')
    {
        $this->curl->setOption(CURLOPT_POST, true);
        $this->curl->setOption(CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
        $this->curl->addHeader('Content-type', 'text/xml');
        $this->curl->addHeader('Expect', ' ');
        $this->curl->setOption(CURLOPT_SSLVERSION, 6);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, 2);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setTimeout(300);

        if ($type == 'transaction') {
            $this->curl->post($this->config->getBaseUrl(), $params);
        } elseif ($type == 'service') {
            $this->curl->post($this->config->getBaseServiceUrl(), $params);
        } else {
            throw new RequestFailedException(__('Unsupported API type: ' . $type));
        }

        $responseCode = $this->curl->getStatus();
        if ($responseCode != 200) {
            throw new RequestFailedException(__('Auth request failed with HTTP code %s.', $responseCode));
        }

        return $this->parseResponse($this->curl->getBody());
    }

    /**
     * @param $method
     * @param $xmlns
     * @return \SimpleXMLElement
     */
    protected function startXml($method, $xmlns)
    {
        $xml = new \SimpleXMLElement("<{$method}></{$method}>");
        $xml->addAttribute('xmlns', $xmlns);

        $application = $xml->addChild('Application');
        $application->addChild('ApplicationID', self::APPLICATION_ID);
        $application->addChild('ApplicationName', self::APPLICATION_NAME);
        $application->addChild('ApplicationVersion', self::APPLICATION_VERSION);

        $credentials = $xml->addChild('Credentials');
        $credentials->addChild('AccountID', $this->config->getAccountID());
        $credentials->addChild('AccountToken', $this->config->getAccountToken());
        $credentials->addChild('AcceptorID', $this->config->getAcceptorID());

        return $xml;
    }

    /**
     * Common transaction XML attributes
     *
     * @param string $transactionName
     * @return \SimpleXMLElement
     */
    protected function startTransactionXml($transactionName)
    {
        return $this->startXml($transactionName, 'https://transaction.elementexpress.com');
    }

    /**
     * Common service XML attributes
     *
     * @param $serviceName
     * @return \SimpleXMLElement
     */
    protected function startServiceXml($serviceName)
    {
        return $this->startXml($serviceName, 'https://services.elementexpress.com');
    }

    /**
     * HealthCheck
     *
     * @return string
     */
    protected function getHealthCheckXmlBody()
    {
        return $this->startTransactionXml('HealthCheck')->asXML();
    }

    /**
     * CreditCardAVSOnly
     *
     * @param $ccNumber
     * @param $ccExpMonth
     * @param $ccExpYear
     * @param $cvv
     * @param $amount
     * @param $referenceNumber
     * @param $ticketNumber
     * @param $paymentAccountID
     * @param array $addressInput
     * @return string
     */
    protected function getCreditCardAvsOnlyXmlBody($ccNumber, $ccExpMonth, $ccExpYear, $cvv, $amount, $referenceNumber = '', $ticketNumber = '', $paymentAccountID = '', $addressInput = [])
    {
        $CreditCardAVSOnly = $this->startTransactionXml('CreditCardAVSOnly');

        $card = $CreditCardAVSOnly->addChild('Card');
        $card->addChild('CardNumber', (string)$ccNumber);
        $card->addChild('ExpirationMonth', substr(str_pad((int)$ccExpMonth, 2, 0, STR_PAD_LEFT), -2));
        $card->addChild('ExpirationYear', substr(str_pad((int)$ccExpYear, 2, 0, STR_PAD_LEFT), -2));
        $card->addChild('CVV', (int)$cvv);

        $address = $CreditCardAVSOnly->addChild('Address');
        /**
         * Vantiv Address Class, as $addressInput is created internally by converting the address info within Magento, the structure of the array is assumed to be correct.
         */
        foreach ($addressInput as $field => $value) {
            $address->addChild($field, $value);
        }

        $transactionSetup = $CreditCardAVSOnly->addChild('TransactionSetup');
        $transactionSetup->addChild('TransactionSetupMethod', 2);
        $transactionSetup->addChild('CVVRequired', 1);
        $transactionSetup->addChild('CompanyName', $this->config->getCompanyName());

        $transactionDetails = $CreditCardAVSOnly->addChild('Transaction');
        $transactionDetails->addChild('TransactionAmount', $this->getNormalizedCurrencyAmount($amount));
        $transactionDetails->addChild('MarketCode', self::MARKET_CODE);
        $transactionDetails->addChild('ReferenceNumber', $referenceNumber);

        if (empty($ticketNumber)) {
            $ticketNumber = $referenceNumber;
        }
        $transactionDetails->addChild('TicketNumber', $ticketNumber); // Per Vantiv documentation, this is required for ECommerce
        $transactionDetails->addChild('PartialApprovedFlag', self::PARTIAL_APPROVED_FLAG);

        if (!empty($paymentAccountID)) {
            $extendedParameters = $CreditCardAVSOnly->addChild('ExtendedParameters');
            $paymentAccount = $extendedParameters->addChild('PaymentAccount');
            $paymentAccount->addChild('PaymentAccountID', $paymentAccountID);
        }

        $terminal = $CreditCardAVSOnly->addChild('Terminal');
        $terminal->addChild('TerminalID', $this->config->getTerminalID());
        $terminal->addChild('CardPresentCode', self::CARD_PRESENT_CODE);
        $terminal->addChild('CardholderPresentCode', self::CARD_HOLDER_PRESENT_CODE);
        $terminal->addChild('CardInputCode', self::CARD_INPUT_CODE);
        $terminal->addChild('CVVPresenceCode', self::CVV_PRESENCE_CODE);
        $terminal->addChild('TerminalCapabilityCode', self::TERMINAL_CAPABILITY_CODE);
        $terminal->addChild('TerminalEnvironmentCode', self::TERMINAL_ENVIRONMENT_CODE);
        $terminal->addChild('MotoECICode', self::MOTO_ECI_CODE);
        $terminal->addChild('TerminalType', self::TERMINAL_TYPE);

        return $CreditCardAVSOnly->asXML();
    }

    /**
     * CreditCardAuthorization
     *
     * @param $ccNumber
     * @param $ccExpMonth
     * @param $ccExpYear
     * @param $cvv
     * @param $amount
     * @param $referenceNumber
     * @param $ticketNumber
     * @param $paymentAccountID
     * @param array $addressInput
     * @return string
     */
    protected function getCreditCardAuthorizationXmlBody($ccNumber, $ccExpMonth, $ccExpYear, $cvv, $amount, $referenceNumber = '', $ticketNumber = '', $paymentAccountID = '', $addressInput = [])
    {
        $creditCardAuthorization = $this->startTransactionXml('CreditCardAuthorization');

        $card = $creditCardAuthorization->addChild('Card');
        $card->addChild('CardNumber', (string)$ccNumber);
        $card->addChild('ExpirationMonth', substr(str_pad((int)$ccExpMonth, 2, 0, STR_PAD_LEFT), -2));
        $card->addChild('ExpirationYear', substr(str_pad((int)$ccExpYear, 2, 0, STR_PAD_LEFT), -2));
        $card->addChild('CVV', (int)$cvv);

        if (!empty($addressInput)) {
            $address = $creditCardAuthorization->addChild('Address');
            /**
             * Vantiv Address Class, as $addressInput is created internally by converting the address info within Magento, the structure of the array is assumed to be correct.
             */
            foreach ($addressInput as $field => $value) {
                $address->addChild($field, $value);
            }
        }

        $transactionSetup = $creditCardAuthorization->addChild('TransactionSetup');
        $transactionSetup->addChild('TransactionSetupMethod', 2);
        $transactionSetup->addChild('CVVRequired', 1);
        $transactionSetup->addChild('CompanyName', $this->config->getCompanyName());

        $transactionDetails = $creditCardAuthorization->addChild('Transaction');
        $transactionDetails->addChild('TransactionAmount', $this->getNormalizedCurrencyAmount($amount));
        $transactionDetails->addChild('MarketCode', self::MARKET_CODE);
        $transactionDetails->addChild('ReferenceNumber', $referenceNumber);

        if (empty($ticketNumber)) {
            $ticketNumber = $referenceNumber;
        }
        $transactionDetails->addChild('TicketNumber', $ticketNumber); // Per Vantiv documentation, this is required for ECommerce
        $transactionDetails->addChild('PartialApprovedFlag', self::PARTIAL_APPROVED_FLAG);

        if (!empty($paymentAccountID)) {
            $extendedParameters = $creditCardAuthorization->addChild('ExtendedParameters');
            $paymentAccount = $extendedParameters->addChild('PaymentAccount');
            $paymentAccount->addChild('PaymentAccountID', $paymentAccountID);
        }

        $terminal = $creditCardAuthorization->addChild('Terminal');
        $terminal->addChild('TerminalID', $this->config->getTerminalID());
        $terminal->addChild('CardPresentCode', self::CARD_PRESENT_CODE);
        $terminal->addChild('CardholderPresentCode', self::CARD_HOLDER_PRESENT_CODE);
        $terminal->addChild('CardInputCode', self::CARD_INPUT_CODE);
        $terminal->addChild('CVVPresenceCode', self::CVV_PRESENCE_CODE);
        $terminal->addChild('TerminalCapabilityCode', self::TERMINAL_CAPABILITY_CODE);
        $terminal->addChild('TerminalEnvironmentCode', self::TERMINAL_ENVIRONMENT_CODE);
        $terminal->addChild('MotoECICode', self::MOTO_ECI_CODE);
        $terminal->addChild('TerminalType', self::TERMINAL_TYPE);

        return $creditCardAuthorization->asXML();
    }

    /**
     * CreditCardSale
     *
     * @param $ccNumber
     * @param $ccExpMonth
     * @param $ccExpYear
     * @param $cvv
     * @param $amount
     * @param $referenceNumber
     * @param $ticketNumber
     * @param $paymentAccountID
     * @param array $addressInput
     * @return string
     */
    protected function getCreditCardSaleXmlBody($ccNumber, $ccExpMonth, $ccExpYear, $cvv, $amount, $referenceNumber = '', $ticketNumber = '', $paymentAccountID = '', $addressInput = [])
    {
        $creditCardSale = $this->startTransactionXml('CreditCardSale');
        $card = $creditCardSale->addChild('Card');
        $card->addChild('CardNumber', (string)$ccNumber);
        $card->addChild('ExpirationMonth', substr(str_pad((int)$ccExpMonth, 2, 0, STR_PAD_LEFT), -2));
        $card->addChild('ExpirationYear', substr(str_pad((int)$ccExpYear, 2, 0, STR_PAD_LEFT), -2));
        $card->addChild('CVV', (int)$cvv);

        if (!empty($addressInput)) {
            $address = $creditCardSale->addChild('Address');
            /**
             * Vantiv Address Class, as $addressInput is created internally by converting the address info within Magento, the structure of the array is assumed to be correct.
             */
            foreach ($addressInput as $field => $value) {
                $address->addChild($field, $value);
            }
        }

        $transactionSetup = $creditCardSale->addChild('TransactionSetup');
        $transactionSetup->addChild('TransactionSetupMethod', 1);
        $transactionSetup->addChild('CVVRequired', 1);
        $transactionSetup->addChild('CompanyName', $this->config->getCompanyName());

        $transactionDetails = $creditCardSale->addChild('Transaction');
        $transactionDetails->addChild('TransactionAmount', $this->getNormalizedCurrencyAmount($amount));
        $transactionDetails->addChild('MarketCode', self::MARKET_CODE);
        $transactionDetails->addChild('ReferenceNumber', $referenceNumber);

        if (empty($ticketNumber)) {
            $ticketNumber = $referenceNumber;
        }
        $transactionDetails->addChild('TicketNumber', $ticketNumber); // Per Vantiv documentation, this is required for ECommerce
        $transactionDetails->addChild('PartialApprovedFlag', self::PARTIAL_APPROVED_FLAG);

        if (!empty($paymentAccountID)) {
            $extendedParameters = $creditCardSale->addChild('ExtendedParameters');
            $paymentAccount = $extendedParameters->addChild('PaymentAccount');
            $paymentAccount->addChild('PaymentAccountID', $paymentAccountID);
        }

        $terminal = $creditCardSale->addChild('Terminal');
        $terminal->addChild('TerminalID', $this->config->getTerminalID());
        $terminal->addChild('CardPresentCode', self::CARD_PRESENT_CODE);
        $terminal->addChild('CardholderPresentCode', self::CARD_HOLDER_PRESENT_CODE);
        $terminal->addChild('CardInputCode', self::CARD_INPUT_CODE);
        $terminal->addChild('CVVPresenceCode', self::CVV_PRESENCE_CODE);
        $terminal->addChild('TerminalCapabilityCode', self::TERMINAL_CAPABILITY_CODE);
        $terminal->addChild('TerminalEnvironmentCode', self::TERMINAL_ENVIRONMENT_CODE);
        $terminal->addChild('MotoECICode', self::MOTO_ECI_CODE);
        $terminal->addChild('TerminalType', self::TERMINAL_TYPE);

        return $creditCardSale->asXML();
    }

    /**
     * CreditCardAuthorizationCompletion
     *
     * @param $transactionID
     * @param $amount
     * @param $referenceNumber
     * @param string $ticketNumber
     * @return string
     */
    protected function getCreditCardAuthorizationCompletionXmlBody($transactionID, $amount, $referenceNumber, $ticketNumber = '')
    {
        $CreditCardAuthorizationCompletion = $this->startTransactionXml('CreditCardAuthorizationCompletion');

        $transactionDetails = $CreditCardAuthorizationCompletion->addChild('Transaction');
        $transactionDetails->addChild('TransactionAmount', $this->getNormalizedCurrencyAmount($amount));
        $transactionDetails->addChild('ReferenceNumber', $referenceNumber);

        if (empty($ticketNumber)) {
            $ticketNumber = $referenceNumber;
        }
        $transactionDetails->addChild('TicketNumber', $ticketNumber); // Per Vantiv documentation, this is required for ECommerce
        $transactionDetails->addChild('TransactionID', $transactionID);
        $transactionDetails->addChild('MarketCode', self::MARKET_CODE);

        $terminal = $CreditCardAuthorizationCompletion->addChild('Terminal');
        $terminal->addChild('TerminalID', $this->config->getTerminalID());
        $terminal->addChild('CardPresentCode', self::CARD_PRESENT_CODE);
        $terminal->addChild('CardholderPresentCode', self::CARD_HOLDER_PRESENT_CODE);
        $terminal->addChild('CardInputCode', self::CARD_INPUT_CODE);
        $terminal->addChild('TerminalCapabilityCode', self::TERMINAL_CAPABILITY_CODE);
        $terminal->addChild('TerminalEnvironmentCode', self::TERMINAL_ENVIRONMENT_CODE);
        $terminal->addChild('MotoECICode', self::MOTO_ECI_CODE);
        $terminal->addChild('TerminalType', self::TERMINAL_TYPE);

        return $CreditCardAuthorizationCompletion->asXML();
    }

    /**
     * CreditCardReturn
     *
     * @param $transactionID
     * @param $amount
     * @param $referenceNumber
     * @param string $ticketNumber
     * @return string
     */
    protected function getCreditCardReturnXmlBody($transactionID, $amount, $referenceNumber, $ticketNumber = '')
    {
        $creditCardReturn = $this->startTransactionXml('CreditCardReturn');

        $transactionDetails = $creditCardReturn->addChild('Transaction');
        $transactionDetails->addChild('TransactionAmount', $this->getNormalizedCurrencyAmount($amount));
        $transactionDetails->addChild('ReferenceNumber', $referenceNumber);
        $transactionDetails->addChild('TransactionID', $transactionID);

        if (empty($ticketNumber)) {
            $ticketNumber = $referenceNumber;
        }
        $transactionDetails->addChild('TicketNumber', $ticketNumber); // Per Vantiv documentation, this is required for ECommerce
        $transactionDetails->addChild('MarketCode', self::MARKET_CODE);

        $terminal = $creditCardReturn->addChild('Terminal');
        $terminal->addChild('TerminalID', $this->config->getTerminalID());
        $terminal->addChild('CardPresentCode', self::CARD_PRESENT_CODE);
        $terminal->addChild('CardholderPresentCode', self::CARD_HOLDER_PRESENT_CODE);
        $terminal->addChild('CardInputCode', self::CARD_INPUT_CODE);
        $terminal->addChild('TerminalCapabilityCode', self::TERMINAL_CAPABILITY_CODE);
        $terminal->addChild('TerminalEnvironmentCode', self::TERMINAL_ENVIRONMENT_CODE);
        $terminal->addChild('MotoECICode', self::MOTO_ECI_CODE);

        return $creditCardReturn->asXML();
    }

    /**
     * @param string $type
     * @param int $transactionID
     * @param $amount
     * @param int $salesTaxAmount
     * @param $referenceNumber
     * @param string $ticketNumber
     * @return string
     */
    protected function getCreditCardReversalXmlBody($type = 'system', $transactionID = 0, $amount, $salesTaxAmount = 0, $referenceNumber, $ticketNumber = '')
    {
        switch ($type) {
            case 'partial':
                $reversalType = self::REVERSAL_TYPE_PARTIAL;
                $transactionIDRequired = true;
                break;
            case 'full':
                $reversalType = self::REVERSAL_TYPE_FULL;
                $transactionIDRequired = true;
                break;
            case 'system':
            default:
                $reversalType = self::REVERSAL_TYPE_SYSTEM;
                $transactionIDRequired = false;
                break;
        }

        $creditCardReversal = $this->startTransactionXml('CreditCardReversal');

        $transactionDetails = $creditCardReversal->addChild('Transaction');
        $transactionDetails->addChild('ReversalType', $reversalType);
        $transactionDetails->addChild('TransactionAmount', $this->getNormalizedCurrencyAmount($amount));
        $transactionDetails->addChild('ReferenceNumber', $referenceNumber);

        if ($salesTaxAmount > 0) {
            $transactionDetails->addChild('SalesTaxAmount', $this->getNormalizedCurrencyAmount($salesTaxAmount));
        }

        if ($transactionIDRequired || $transactionID > 0) {
            $transactionDetails->addChild('TransactionID', $transactionID);
        }

        if (empty($ticketNumber)) {
            $ticketNumber = $referenceNumber;
        }
        $transactionDetails->addChild('TicketNumber', $ticketNumber); // Per Vantiv documentation, this is required for ECommerce
        $transactionDetails->addChild('MarketCode', self::MARKET_CODE);

        $terminal = $creditCardReversal->addChild('Terminal');
        $terminal->addChild('TerminalID', $this->config->getTerminalID());
        $terminal->addChild('CardPresentCode', self::CARD_PRESENT_CODE);
        $terminal->addChild('CardholderPresentCode', self::CARD_HOLDER_PRESENT_CODE);
        $terminal->addChild('CardInputCode', self::CARD_INPUT_CODE);
        $terminal->addChild('CVVPresenceCode', self::CVV_PRESENCE_CODE);
        $terminal->addChild('TerminalCapabilityCode', self::TERMINAL_CAPABILITY_CODE);
        $terminal->addChild('TerminalEnvironmentCode', self::TERMINAL_ENVIRONMENT_CODE);
        $terminal->addChild('MotoECICode', self::MOTO_ECI_CODE);

        return $creditCardReversal->asXML();
    }

    /**
     * @param $ccNumber
     * @param $ccExpMonth
     * @param $ccExpYear
     * @param string $referenceNumber
     * @return string
     */
    protected function getPaymentAccountCreateXmlBody($ccNumber, $ccExpMonth, $ccExpYear, $referenceNumber)
    {
        $paymentAccountCreate = $this->startServiceXml('PaymentAccountCreate');

        $paymentAccount = $paymentAccountCreate->addChild('PaymentAccount');
        $paymentAccount->addChild('PaymentAccountType', self::PAYMENT_ACCOUNT_TYPE);
        $paymentAccount->addChild('PaymentAccountReferenceNumber', $referenceNumber);

        $card = $paymentAccountCreate->addChild('Card');
        $card->addChild('CardNumber', (string)$ccNumber);
        $card->addChild('ExpirationMonth', substr(str_pad((int)$ccExpMonth, 2, 0, STR_PAD_LEFT), -2));
        $card->addChild('ExpirationYear', substr(str_pad((int)$ccExpYear, 2, 0, STR_PAD_LEFT), -2));

        return $paymentAccountCreate->asXML();
    }

    /**
     * @param $response
     * @return \SimpleXMLElement
     * @throws InvalidXmlException
     */
    protected function parseResponse($response)
    {
        $xml = simplexml_load_string($response);

        if ($xml !== false) {
            if (property_exists($xml, 'Response')) {
                return $xml->Response;
            }
            return $xml;
        } else {
            foreach (libxml_get_errors() as $error) {
                $errors[] = $error->message;
            }
            if (!empty($errors)) {
                $message = implode(', ', $errors);
            } else {
                $message = 'Remote API did not return a proper xml response.';
            }
            throw new InvalidXmlException($message);
        }
    }

    /**
     * @param \SimpleXMLElement $response
     * @param string $key
     * @return null|\SimpleXMLElement
     */
    protected function getResponseObjectByKey(\SimpleXMLElement $response, $key)
    {
        if (property_exists($response, $key)) {
            return $response->{$key};
        }
        return null;
    }

    /**
     * Enforces correct dollar float amount to avoid Vantiv API errors
     *
     * @param int|float $amount
     * @return string
     */
    protected function getNormalizedCurrencyAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Returns active status of Certification config. Used to enable/disable Certification process.
     *
     * @return bool
     */
    public function isCertificationEnabled()
    {
        return (bool)$this->config->getCertificationActiveStatus();
    }

    /**
     * Returns active status of API Credit Card pre-auth. Used to enable/disable PaymentAccountCreate call during checkout.
     *
     * @return bool
     */
    public function isApiCCAuthEnabled()
    {
        return (bool)$this->config->getApiCreditCardAuthorizationModeActiveStatus();
    }

    /**
     * Returns the display notice message for users that fail the AVS check during checkout
     *
     * @return string
     */
    public function getApiAvsFailedUserMessage()
    {
        return (string)$this->config->getApiAvsFailedUserMessage();
    }

    /**
     * Given a Magento Address Object, create the corresponding address array inline with the API syntax.
     *
     * @param Address $address
     * @return array
     */
    public function getAddressArray(Address $address)
    {
        $name = implode(' ', array_filter([$address->getFirstname(), $address->getMiddlename(), $address->getLastname()]));
        return [
            'BillingName' => $name,
            'BillingEmail' => $address->getEmail(),
            'BillingPhone' => $address->getTelephone(),
            'BillingAddress1' => $address->getStreetLine(1),
            'BillingAddress2' => $address->getStreetLine(2),
            'BillingCity' => $address->getCity(),
            'BillingState' => $address->getRegion(),
            'BillingZipcode' => $address->getPostcode(),
        ];
    }
}
