<?php

namespace Ripen\VantivIntegratedPayments\Controller\Gateway;

use Magento\Framework\App\Action\Context;
use Ripen\VantivIntegratedPayments\Exception\CertificationInactiveException;
use Ripen\VantivIntegratedPayments\Exception\RequestFailedException;
use Ripen\VantivIntegratedPayments\Exception\TransactionAuthorizationFailedException;
use Ripen\VantivIntegratedPayments\Model\Api;

class Certification extends \Magento\Framework\App\Action\Action
{
    protected $api;

    protected $visa = [
        'number' => '4457010000000009',
        'exp_month' => '12',
        'exp_year' => '22',
        'cvv' => '349',
    ];

    protected $masterCard = [
        'number' => '5435101234510196',
        'exp_month' => '12',
        'exp_year' => '22',
        'cvv' => '987',
    ];

    public function __construct(
        Context $context,
        Api $api
    ) {
        parent::__construct($context);

        $this->api = $api;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws CertificationInactiveException
     * @throws RequestFailedException
     */
    public function execute()
    {
        if (!$this->api->isCertificationEnabled()) {
            throw new CertificationInactiveException(__('Certification is currently not active.'));
        }

        echo '<h1>HealthCheck</h1>';
        $this->healthCheck();

        echo '<h1>CreditCardAuthorization - Visa Keyed with CVV for $5.51</h1>';
        $this->CreditCardAuthorization('visa', '5.51');

        echo '<h1>CreditCardAuthorization - Visa Keyed partial approval for $23.05</h1>';
        $this->CreditCardAuthorization('visa', '23.05');

        echo '<h1>CreditCardAuthorization - Visa Keyed with CVV for $23.06, Balance and Currency Code Returned</h1>';
        $this->CreditCardAuthorization('visa', '23.06');

        echo '<h1>CreditCardAuthorizationCompletion - Visa $5.51</h1>';
        $this->CreditCardAuthorizationCompletion('visa', '5.51');

        echo '<h1>CreditCardAuthorizationCompletion - MasterCard $5.52</h1>';
        $this->CreditCardAuthorizationCompletion('mastercard', '5.52');

        echo '<h1>CreditCardAuthorizationCompletion - Visa $5.53</h1>';
        $this->CreditCardAuthorizationCompletion('visa', '5.53');

        echo '<h1>CreditCardAuthorizationCompletion - Visa partial approval $20.00</h1>';
        $this->CreditCardAuthorizationCompletion('visa', '20.00');

        echo '<h1>CreditCardReversal - Visa keyed System Reversal $10.01</h1>';
        $this->CreditCardReversal('system', 'visa', '10.01');

        echo '<h1>CreditCardReversal - Visa keyed full Reversal $6.13</h1>';
        $this->CreditCardReversal('full', 'visa', '6.13');
    }

    public function healthCheck()
    {
        $response = $this->api->healthCheck();

        $this->displayResponse('HealthCheck', $response);
    }

    /**
     * @param string $type
     * @param $amount
     * @param bool $returnResponse
     * @return \SimpleXMLElement
     * @throws RequestFailedException
     */
    public function CreditCardAuthorization($type = 'visa', $amount, $returnResponse = false)
    {
        if ($type == 'visa') {
            $card = $this->visa;
        }
        else {
            $card = $this->masterCard;
        }

        $response = $this->api->makeAuthRequest(
            $card['number'],
            $card['exp_month'],
            $card['exp_year'],
            $card['cvv'],
            $amount,
            time()
        );

        if ($returnResponse) {
            return $response;
        }
        $this->displayResponse(__METHOD__, $response);
    }

    /**
     * @param string $type
     * @param $amount
     * @throws RequestFailedException
     */
    public function CreditCardAuthorizationCompletion($type = 'visa', $amount)
    {
        $auth = $this->CreditCardAuthorization($type, $amount, true);

        $authTransactionID = $this->api->getTransactionId($auth);
        $referenceNumber = $this->api->getReferenceNumber($auth);

        $response = $this->api->completeAuth(
            $authTransactionID,
            $amount,
            $referenceNumber
        );

        $this->displayResponse(__METHOD__, $response);
    }

    /**
     * @param $reversalType
     * @param string $type
     * @param $amount
     * @param int $salesTaxAmount
     * @throws RequestFailedException
     */
    public function CreditCardReversal($reversalType, $type = 'visa', $amount, $salesTaxAmount = 0)
    {
        $auth = $this->CreditCardAuthorization($type, $amount, true);

        $authTransactionID = $this->api->getTransactionId($auth);
        $referenceNumber = $this->api->getReferenceNumber($auth);

        $response = $this->api->makeReversal(
            $reversalType,
            $authTransactionID,
            $amount,
            $salesTaxAmount,
            $referenceNumber
        );

        $this->displayResponse(__METHOD__, $response);
    }

    protected function displayResponse($method, $response)
    {
        echo '<pre>';
        print_r($response);
        echo '</pre><hr/>';
    }
}
