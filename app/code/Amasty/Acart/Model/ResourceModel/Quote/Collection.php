<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */

namespace Amasty\Acart\Model\ResourceModel\Quote;

class Collection extends \Magento\Quote\Model\ResourceModel\Quote\Collection
{

    public function addAbandonedCartsFilter()
    {
        $this->addFieldToFilter('main_table.is_active', array(
            'eq' => 1
        ))
        ->getSelect()
        ->joinLeft(
            ['ruleQuote' => $this->getTable('amasty_acart_rule_quote')],
            'main_table.entity_id = ruleQuote.quote_id and ruleQuote.test_mode <> 1',
            ['rule_quote_id']
        )
        ->where(
            'ruleQuote.rule_quote_id is null and main_table.items_count > 0'
        );

        $this->joinQuoteEmail();

        return $this;
    }

    public function joinQuoteEmail()
    {
        $this->getSelect()->joinLeft(
            ['quoteEmail' => $this->getTable('amasty_acart_quote_email')],
            'main_table.entity_id = quoteEmail.quote_id',
            ['acart_quote_email' => 'customer_email']
        )->columns('ifnull(main_table.customer_email, quoteEmail.customer_email) as target_email');

        return $this;
    }

    public function addTimeFilter($currentExecution, $lastExecution)
    {
        $this->addFieldToFilter(new \Zend_Db_Expr('IF(main_table.created_at > main_table.updated_at, main_table.created_at, main_table.updated_at)'), array(
            'gteq' => $lastExecution
        ))->addFieldToFilter(new \Zend_Db_Expr('IF(main_table.created_at > main_table.updated_at, main_table.created_at, main_table.updated_at)'), array(
            'lt' => $currentExecution
        ));

        return $this;
    }
}