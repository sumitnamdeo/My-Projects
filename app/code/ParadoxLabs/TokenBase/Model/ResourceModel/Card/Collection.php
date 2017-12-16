<?php
/**
 * Card model collection
 *
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author        Ryan Hoerr <support@paradoxlabs.com>
 * @license        http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Model\ResourceModel\Card;

/**
 * Collection Class
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ParadoxLabs\TokenBase\Model\Card', 'ParadoxLabs\TokenBase\Model\ResourceModel\Card');
    }
}
