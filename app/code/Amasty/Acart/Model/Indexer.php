<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */

namespace Amasty\Acart\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Indexer extends \Magento\Framework\DataObject
{
    protected $_resourceQuoteFactory;
    protected $_resourceRuleFactory;
    protected $_resourceHistoryFactory;
    protected $_resourceRuleQuoteFactory;

    protected $_resourceConfig;

    protected $_helper;
    protected $_dateTime;
    protected $_date;

    protected $_actualGap = 172800; //2 days
    protected $_lastExecution = null;
    protected $_currentExecution  = null;

    const LAST_EXECUTED_PATH = 'amasty_acart/common/last_executed';

    public function __construct(
        \Amasty\Acart\Model\ResourceModel\Quote\CollectionFactory $resourceQuoteFactory,
        \Amasty\Acart\Model\ResourceModel\Rule\CollectionFactory $resourceRuleFactory,
        \Amasty\Acart\Model\ResourceModel\History\CollectionFactory $resourceHistoryFactory,
        \Amasty\Acart\Model\ResourceModel\RuleQuote\CollectionFactory $resourceRuleQuoteFactory,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Amasty\Acart\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        ScopeConfigInterface $scopeConfig,

        array $data = []
    ){
        $this->_resourceQuoteFactory = $resourceQuoteFactory;
        $this->_resourceRuleFactory = $resourceRuleFactory;
        $this->_resourceHistoryFactory = $resourceHistoryFactory;
        $this->_resourceRuleQuoteFactory = $resourceRuleQuoteFactory;

        $this->_resourceConfig = $resourceConfig;
        $this->_dateTime = $dateTime;
        $this->_date = $date;

        $this->_helper = $helper;

        $this->_scopeConfig = $scopeConfig;

        return parent::__construct($data);
    }

    public function run()
    {
        $this->_prepare();
        $this->_execute();
    }

    protected function _prepare()
    {
        $processedQuotes = array();

        $resourceQuote = $this->_resourceQuoteFactory->create()
            ->addAbandonedCartsFilter();
//            ->addTimeFilter(
//                $this->_dateTime->formatDate($this->_getCurrentExecution()),
//                $this->_dateTime->formatDate($this->_getLastExecution())
//            );

        if ($this->_scopeConfig->getValue('amasty_acart/general/only_customers')){
            $resourceQuote->addFieldToFilter('main_table.customer_id', ['notnull'=> true]);
        }

        $resourceRule = $this->_resourceRuleFactory->create()
            ->addFieldToFilter('is_active', \Amasty\Acart\Model\Rule::RULE_ACTIVE)
            ->addOrder('priority', \Amasty\Acart\Model\ResourceModel\Quote\Collection::SORT_ORDER_ASC);
        
        foreach($resourceRule as $rule)
        {
            foreach($resourceQuote as $quote)
            {
                if (!in_array($quote->getId(), $processedQuotes) && $rule->validate($quote)) {

                    \Magento\Framework\App\ObjectManager::getInstance()
                        ->create('Amasty\Acart\Model\RuleQuote')
                        ->createRuleQuote($rule, $quote);

                    $processedQuotes[] = $quote->getId();
                }
            }
        }
    }

    protected function _execute()
    {
        $resourceHistory = $this->_resourceHistoryFactory->create()
            ->addRuleQuoteData()
            ->addTimeFilter(
                $this->_dateTime->formatDate($this->_getCurrentExecution()),
                $this->_dateTime->formatDate($this->_getLastExecution())
            )->addFieldToFilter('ruleQuote.status', \Amasty\Acart\Model\RuleQuote::STATUS_PROCESSING);

        foreach($resourceHistory as $history){
            $history->execute();
        }

        $resourceRuleQuote = $this->_resourceRuleQuoteFactory->create();

        foreach($resourceRuleQuote->addCompleteFilter() as $ruleQuote){
            $ruleQuote->complete();
        }
    }

    protected function _getLastExecution()
    {
        if ($this->_lastExecution === null){
            $this->_lastExecution = (string) $this->_helper->getScopeValue(self::LAST_EXECUTED_PATH);
            if (empty($this->_lastExecution)){
                $this->_lastExecution = $this->_date->gmtTimestamp() - $this->_actualGap;
            }
            $this->_currentExecution = $this->_date->gmtTimestamp();

            $this->_resourceConfig->saveConfig(self::LAST_EXECUTED_PATH, $this->_currentExecution, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        }
        return $this->_lastExecution;
    }

    protected function _getCurrentExecution()
    {
        return $this->_currentExecution ? $this->_currentExecution : $this->_date->gmtTimestamp();
    }
}