<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */

namespace Amasty\Acart\Model\ResourceModel\History;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Amasty\Acart\Model\History', 'Amasty\Acart\Model\ResourceModel\History');
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }

    public function addTimeFilter($currentExecution, $lastExecution)
    {
        $this
            ->addFieldToFilter('main_table.scheduled_at', array(
                'gteq' => $lastExecution
            ))->addFieldToFilter('main_table.scheduled_at', array(
                'lt' => $currentExecution
            ))
            ->getSelect()
            ->where(
                'main_table.status = ?', \Amasty\Acart\Model\History::STATUS_PROCESSING
            );
        return $this;
    }

    public function addRuleQuoteData()
    {
        $this->getSelect()
            ->joinLeft(
                        ['ruleQuote' => $this->getTable('amasty_acart_rule_quote')],
                        'main_table.rule_quote_id = ruleQuote.rule_quote_id',
                        ['store_id', 'customer_id', 'customer_email', 'customer_firstname', 'customer_lastname', 'quote_id']
                    );

        return $this;
    }

    public function addRuleData()
    {
        $this->getSelect()
            ->joinLeft(
                        ['rule' => $this->getTable('amasty_acart_rule')],
                        'ruleQuote.rule_id = rule.rule_id',
                        ['name']
                    );

        return $this;
    }

}