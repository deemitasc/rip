<?php

namespace Vekeryk\SaveCart\Block\Adminhtml\Edit\Tab;

use Magento\Customer\Controller\RegistryConstants;

/**
 * Adminhtml customer orders grid block
 */
class SavedCarts extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Sales reorder
     *
     * @var \Magento\Sales\Helper\Reorder
     */
    protected $_salesReorder = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var  \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory
     */
    protected $collectionFactory;

    private $saveCart;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $collectionFactory
     * @param \Magento\Sales\Helper\Reorder $salesReorder
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $collectionFactory,
        \Magento\Sales\Helper\Reorder $salesReorder,
        \Magento\Framework\Registry $coreRegistry,
        \Vekeryk\SaveCart\Model\SaveCart $saveCart,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_salesReorder = $salesReorder;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
        $this->saveCart = $saveCart;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customer_savedcarts_grid');
        $this->setDefaultSort('created_at', 'desc');
        $this->setUseAjax(true);
    }

    /**
     * Apply various selection filters to prepare the sales order grid collection.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->getReport('savecart_quote_grid_data_source')
            ->addFieldToSelect(
                'entity_id'
            )->addFieldToFilter(
                'is_active',
                1
            )->addFieldToFilter(
                'main_table.customer_id',
                $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID)
            );
        $this->saveCart->addSavedCartInfoToCollection($collection);

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id',
            [
                'header' => __('Quote #'),
                'width' => '100',
                'index' => 'entity_id',
                'filter_index' => 'main_table.entity_id',
                'renderer' => 'Vekeryk\SaveCart\Block\Adminhtml\Grid\Renderer\QuoteNumber'
            ]
        );

        $this->addColumn(
            'quote_name',
            [
                'header' => __('Name'),
                'index' => 'quote_name',
                'filter_index' => 'VSC.quote_name',
            ]
        );

        $this->addColumn(
            'vsc_created_at',
            [
                'header' => __('Created'),
                'index' => 'vsc_created_at',
                'filter_index' => 'VSC.created_at',
                'type' => 'datetime'
            ]
        );

        $this->addColumn(
            'action',
            [
                'header' => 'Action',
                'filter' => false,
                'sortable' => false,
                'width' => '100px',
                'renderer' => 'Vekeryk\SaveCart\Block\Adminhtml\Grid\Renderer\View'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @inheritdoc
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('savecart/*/viewCart', ['quote_id' => $row->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getGridUrl()
    {
        return $this->getUrl('savecart/*/savedCarts', ['_current' => true]);
    }
}
