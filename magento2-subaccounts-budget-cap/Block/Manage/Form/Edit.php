<?php

namespace Ripen\BudgetCap\Block\Manage\Form;

use Cminds\MultiUserAccounts\Api\Data\SubaccountInterface;
use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Cminds\MultiUserAccounts\Helper\View;
use Cminds\MultiUserAccounts\Model\Config;
use Cminds\MultiUserAccounts\Model\Permission;
use Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory;
use Magento\Customer\Model\Session as Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Helper\Address;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Cminds\MultiUserAccounts\Helper\Manage as ManageHelper;


class Edit extends \Cminds\MultiUserAccounts\Block\Manage\Form\Edit
{

    /**
     * Session object.
     *
     * @var Session
     */
    private $customerSession;

    /**
     * Customer address object.
     *
     * @var Address
     */
    private $customerAddressHelper;

    /**
     * Permission object.
     *
     * @var Permission
     */
    private $permission;

    /**
     * Subaccount object.
     *
     * @var SubaccountInterface
     */
    private $subaccount;

    /**
     * Customer Repository object.
     *
     * @var
     */
    private $customerRepository;

    /**
     * @var ManageHelper
     */
    private $manageHelper;

    /**
     * @var View
     */
    protected $viewHelper;

    /**
     * @var SubaccountTransportRepositoryInterface
     */
    protected $subaccountTransportRepositoryInterface;

    /**
     * @var Config
     */
    protected $moduleConfig;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Ripen\BudgetCap\Helper\Data
     */
    protected $helper;
    /**
     * @param Context $context
     * @param Session $session
     * @param Permission $permission
     * @param Address $address
     * @param CustomerRepositoryInterface $customerRepository
     * @param ManageHelper $manageHelper
     * @param View $viewHelper
     * @param Config $moduleConfig
     * @param SubaccountTransportRepositoryInterface $subaccountTransportRepositoryInterface
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $session,
        Permission $permission,
        Address $address,
        CustomerRepositoryInterface $customerRepository,
        ManageHelper $manageHelper,
        View $viewHelper,
        Config $moduleConfig,
        SubaccountTransportRepositoryInterface $subaccountTransportRepositoryInterface,
        CollectionFactory $collectionFactory,
        array $data = [],
        \Ripen\BudgetCap\Helper\Data $helper
    ) {
        $this->customerSession = $session;
        $this->permission = $permission;
        $this->customerAddressHelper = $address;
        $this->customerRepository = $customerRepository;
        $this->manageHelper = $manageHelper;
        $this->viewHelper = $viewHelper;
        $this->subaccountTransportRepositoryInterface = $subaccountTransportRepositoryInterface;
        $this->moduleConfig = $moduleConfig;
        $this->collectionFactory = $collectionFactory;
        $this->helper = $helper;

        parent::__construct(
            $context,
            $session,
            $permission,
            $address,
            $customerRepository,
            $manageHelper,
            $viewHelper,
            $moduleConfig,
            $subaccountTransportRepositoryInterface,
            $collectionFactory,
            $data
            );
    }

    public function getBudgetCapPeriods(){
        return $this->helper->getBudgetCapPeriods();
    }
}
