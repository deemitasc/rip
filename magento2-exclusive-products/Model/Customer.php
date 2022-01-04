<?php
namespace Ripen\ExclusiveProducts\Model;

use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\Product;

class Customer
{
    const RESTRICTION_MODE_BLOCK = 'block';
    const RESTRICTION_MODE_ALLOW = 'allow';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param LoggerInterface $logger
     * @param SessionFactory $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LoggerInterface $logger,
        SessionFactory $customerSession,
        CustomerRepositoryInterface $customerRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->customerSession = $customerSession->create();
        $this->customerRepository = $customerRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Checks customer's configured restriction mode
     *
     * @param int $customerId
     * @return string|null
     */
    public function getCustomerRestrictionMode($customerId = null)
    {
        try {
            if (! $customerId) {
                $customerId = $this->customerSession->getCustomerId();
            }

            // guest customers do not have configured restriction mode
            if (! $customerId) {
                return null;
            }

            $customer = $this->customerRepository->getById($customerId);

            $restrictionMode = $customer->getCustomAttribute('restriction_mode');

            return $restrictionMode ? $restrictionMode->getValue() : null;
        } catch (\Throwable $e) {
            // If an exception occurs, treat the customer like a guest customer rather than
            // throw a fatal error, but make a log since this should not happen.
            $this->logger->error(
                "Error looking up exclusive product customer mode for customer $customerId: "
                . $e->getMessage()
            );

            return null;
        }
    }

    /**
     * Returns the value of the identifier attribute (as defined in the config) for the given customer.
     * If customer not provided, defaults to currently logged in customer.
     * If specific identifier attribute not configured, falls back to Magento customer ID.
     *
     * @param int $customerId Magento customer ID
     * @return string|null
     * @throws LocalizedException
     */
    public function getCustomerIdentifier($customerId = null)
    {
        try {
            if (! $customerId) {
                $customerId = $this->customerSession->getCustomerId();
            }

            // Return null for a guest customer.
            if (is_null($customerId)) {
                return null;
            }

            $customerIdAttribute = $this->getCustomerIdentifierAttribute();

            // Fall back to Magento customer entity ID if no specific attribute configured.
            if (empty($customerIdAttribute)) {
                return $customerId;
            }

            $customer = $this->customerRepository->getById($customerId);

            $customerIdentifier = $customer->getCustomAttribute($customerIdAttribute);

            return $customerIdentifier ? $customerIdentifier->getValue() : null;
        } catch (\Throwable $e) {
            // If an exception occurs, treat the customer like a guest customer rather than
            // throw a fatal error, but make a log since this should not happen.
            $this->logger->error(
                "Error looking up exclusive product identifier for customer $customerId: "
                . $e->getMessage()
            );

            return null;
        }
    }

    /**
     * @param string $restrictionMode
     * @return bool
     */
    public function isRestrictionModeBlock($restrictionMode)
    {
        return ($restrictionMode === self::RESTRICTION_MODE_BLOCK);
    }

    /**
     * @param string $restrictionMode
     * @return bool
     */
    public function isRestrictionModeAllow($restrictionMode)
    {
        return ($restrictionMode === self::RESTRICTION_MODE_ALLOW);
    }

    /**
     * @param Product $product
     * @param string|null $customerId Magento customer ID
     * @return bool
     */
    public function isCustomerAllowedOnProduct(Product $product, $customerId = null)
    {
        $customerIdentifier = $this->getCustomerIdentifier($customerId);

        // exclusive check
        $isExclusiveTo = false;
        $productIsExclusive = false;
        $exclusiveToCustomerIdsAttribute = $product->getCustomAttribute('exclusive_to');
        if (!is_null($exclusiveToCustomerIdsAttribute)) {
            $productIsExclusive = true;

            $exclusiveToCustomerIds = explode(' ', $exclusiveToCustomerIdsAttribute->getValue());
            if (in_array($customerIdentifier, $exclusiveToCustomerIds)) {
                $isExclusiveTo = true;
            }
        }

        $isBlocked = false;
        $isAllowed = true;

        if (!is_null($customerIdentifier)) {
            $restrictionMode = $this->getCustomerRestrictionMode($customerId);

            // block check
            if ($this->isRestrictionModeBlock($restrictionMode)) {
                $blockCustomerIdsAttribute = $product->getCustomAttribute('blocked_for');
                if (!is_null($blockCustomerIdsAttribute)) {
                    $blockCustomerIds = explode(' ', $blockCustomerIdsAttribute->getValue());

                    if (in_array($customerIdentifier, $blockCustomerIds)) {
                        $isBlocked = true;
                    }
                }
            }

            // allow check
            if ($this->isRestrictionModeAllow($restrictionMode)) {
                $allowCustomerIdsAttribute = $product->getCustomAttribute('allowed_for');
                if (!is_null($allowCustomerIdsAttribute)) {
                    $allowCustomerIds = explode(' ', $allowCustomerIdsAttribute->getValue());

                    if (!in_array($customerIdentifier, $allowCustomerIds)) {
                        $isAllowed = false;
                    }
                }
            }
        }

        return (
            ($productIsExclusive && $isExclusiveTo || ! $productIsExclusive)
            && (! $isBlocked)
            && $isAllowed
        );
    }

    /**
     * @return string
     */
    protected function getCustomerIdentifierAttribute()
    {
        return (string) $this->scopeConfig->getValue('catalog/exclusive_products/customer_id_attribute', ScopeInterface::SCOPE_STORE);
    }
}
