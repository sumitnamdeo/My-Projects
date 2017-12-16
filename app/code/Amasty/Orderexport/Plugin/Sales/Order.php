<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */

namespace Amasty\Orderexport\Plugin\Sales;

class Order
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;


    /**
     * @var \Amasty\Orderexport\Model\ResourceModel\Profiles\Collection
     */
    protected $_profilesCollection;

    /**
     * @var \Amasty\Orderexport\Helper\Data
     */
    protected $_helper;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Amasty\Orderexport\Model\ResourceModel\Profiles\Collection $profilesCollection,
        \Amasty\Orderexport\Helper\Data $helper
    ) {
        $this->_helper             = $helper;
        $this->_registry           = $registry;
        $this->_objectManager      = $objectManager;
        $this->_profilesCollection = $profilesCollection;
    }


    public function afterSave($subject, $value)
    {
        if (!$this->_helper->getModuleConfig('general/enabled')) {
            return $value;
        }

        if ($this->_registry->registry('amorderexport_manual_run_triggered')) {
            return $value;
        }

        $collection = $this->_profilesCollection->addFieldToFilter('run_after_order_creation', 1);

        foreach ($collection as $profile) {
            $profile->run();
        }

        return $value;
    }

}
