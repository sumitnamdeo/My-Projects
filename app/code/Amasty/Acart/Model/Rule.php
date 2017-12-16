<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */

namespace Amasty\Acart\Model;

class Rule extends \Magento\Framework\Model\AbstractModel
{
    const CANCEL_CONDITION_PLACED = 'placed';
    const CANCEL_CONDITION_CLICKED = 'clicked';

    const RULE_ACTIVE = '1';
    const RULE_INACTIVE = '0';

    protected $_salesRule;
    protected $_scheduleCollection;
    protected $_dateTime;
    protected $_date;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ){
        $this->_dateTime = $dateTime;
        $this->_date = $date;

        return parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }
    public function _construct()
    {
        $this->_init('Amasty\Acart\Model\ResourceModel\Rule');
    }

    public function getSalesRule()
    {
        if (!$this->_salesRule)
        {
            $this->_salesRule = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Amasty\Acart\Model\SalesRule')->load($this->getId());
        }
        return $this->_salesRule;
    }

    public function saveSchedule()
    {
        $schedule = $this->getSchedule();

        $savedIds = [];


        if (is_array($schedule) && count($schedule) > 0){
            foreach($schedule as $config) {
                $object = \Magento\Framework\App\ObjectManager::getInstance()
                                ->create('Amasty\Acart\Model\Schedule');

                if (isset($config['schedule_id'])){
                    $object->load($config['schedule_id']);
                }

                $deliveryTime = $config['delivery_time'];
                $coupon = $config['coupon'];

                if (!isset($coupon['use_shopping_cart_rule'])) {
                    $coupon['use_shopping_cart_rule'] = false;
                }

                $object->addData(array_merge(array(
                    'rule_id' => $this->getId(),
                    'template_id' => $config['template_id'],
                    'created_at' => $this->_dateTime->formatDate($this->_date->gmtTimestamp())
                ), $deliveryTime, $coupon));

                $object->save();

                $savedIds[] = $object->getId();
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('The schedule should be completed.'));
        }

        $deleteCollection = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Amasty\Acart\Model\Schedule')->getCollection()
            ->addFieldToFilter('rule_id', $this->getId())
            ->addFieldToFilter('schedule_id', array(
                'nin' => $savedIds
            ));

        foreach($deleteCollection as $delete){
            $delete->delete();
        }

        $ruleProductAttributes = $this->_getUsedAttributes($this->getConditionsSerialized());

        if (count($ruleProductAttributes)) {
            $this->getResource()->saveAttributes($this->getId(), $ruleProductAttributes);
        }
    }

    protected function _getUsedAttributes($serializedString)
    {
        $result = array();
        $pattern = '~s:46:"Magento\\\SalesRule\\\Model\\\Rule\\\Condition\\\Product";s:9:"attribute";s:\d+:"(.*?)"~s';
        $matches = array();
        if (preg_match_all($pattern, $serializedString, $matches)){
            foreach ($matches[1] as $attributeCode) {
                $result[] = $attributeCode;
            }
        }

        return $result;
    }

    public function getScheduleCollection()
    {
        if (!$this->_scheduleCollection)
            $this->_scheduleCollection = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Amasty\Acart\Model\Schedule')->getCollection()
                ->addFieldToFilter('rule_id', $this->getId());

        return $this->_scheduleCollection;
    }


    protected function _validateAddress(\Magento\Quote\Model\Quote $quote)
    {
        $ret = false;

        foreach($quote->getAllAddresses() as $address){
            $address->setCollectShippingRates(true);
            $address->collectShippingRates();

            if ($this->getSalesRule()->validate($address)){
                $ret = true;
                break;
            }
        }

        return $ret;
    }


    public function validate(\Magento\Quote\Model\Quote $quote)
    {
        $storesIds = $this->getStoreIds();
        $customerGroupIds = $this->getCustomerGroupIds();

        $validStore = !empty($storesIds) ? in_array($quote->getStoreId(), explode(',', $storesIds)) : true;

        $validCustomerGroup = !empty($customerGroupIds) ? in_array($quote->getCustomerGroupId(), explode(',', $customerGroupIds)) : true;

        return $validStore && $validCustomerGroup && $this->_validateAddress($quote);
    }
}