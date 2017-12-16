<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */

namespace Amasty\Orderexport\Model;

/**
 * Class Profiles
 *
 * @package Amasty\Orderexport\Model
 */
class Profiles extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var  \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var  \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * @var \Amasty\Orderexport\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Amasty\Orderexport\Model\History
     */
    protected $_history;

    /**
     * @var \Amasty\Orderexport\Helper\Filter
     */
    protected $_helperFilter;

    /**
     * @var \Amasty\Orderexport\Helper\Export
     */
    protected $_helperExport;

    /**
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                  $date
     * @param \Magento\Framework\ObjectManagerInterface                    $objectManager
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager
     * @param \Magento\Customer\Model\Session                              $customerSession
     * @param \Magento\Catalog\Model\Product                               $product
     * @param \Amasty\Orderexport\Helper\Filter                            $helperFilter
     * @param \Amasty\Orderexport\Helper\Export                            $helperExport
     * @param \Amasty\Orderexport\Helper\Data                              $helper
     * @param \Amasty\Orderexport\Model\History                            $history
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|NULL $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|NULL           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Model\Product $product,
        \Amasty\Orderexport\Helper\Filter $helperFilter,
        \Amasty\Orderexport\Helper\Export $helperExport,
        \Amasty\Orderexport\Helper\Data $helper,
        \Amasty\Orderexport\Model\History $history,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = NULL,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = NULL,
        array $data = []
    ) {
        $this->date             = $date;
        $this->_product         = $product;
        $this->_storeManager    = $storeManager;
        $this->_objectManager   = $objectManager;
        $this->_customerSession = $customerSession;
        $this->_history         = $history;
        $this->_helper          = $helper;
        $this->_helperExport    = $helperExport;
        $this->_helperFilter    = $helperFilter;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return bool
     */
    public function run($ids)
    {
        try {
            $this->setData('time_start', microtime(true));
            $orders = $this->_helperFilter->getOrdersForProfile($this, $ids);
            $output = $this->_helperExport->exportOrders($orders, $this);
            $historyId = $this->_history->addHistory($this);
        } catch (\Exception $e) {
            return false;
        }

        return $historyId;
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Amasty\Orderexport\Model\ResourceModel\Profiles');
        $this->setIdFieldName('entity_id');
    }
}
