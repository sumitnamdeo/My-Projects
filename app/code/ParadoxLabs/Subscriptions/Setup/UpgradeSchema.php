<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 */

namespace ParadoxLabs\Subscriptions\Setup;

/**
 * DB upgrade script for Subscriptions
 */
class UpgradeSchema implements \Magento\Framework\Setup\UpgradeSchemaInterface
{
    /**
     * DB upgrade code
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     */
    public function upgrade(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $quoteDb = $setup->getConnection('checkout');

        /**
         * paradoxlabs_subscription_log.order_id
         */
        $orderIdExists = $quoteDb->tableColumnExists($setup->getTable('paradoxlabs_subscription_log'), 'order_id');
        if ($orderIdExists !== true) {
            /**
             * Add order_id column
             */
            $quoteDb->addColumn(
                $setup->getTable('paradoxlabs_subscription_log'),
                'order_id',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'comment'   => 'Order ID',
                    'unsigned'  => true,
                    'nullable'  => true,
                ]
            );

            /**
             * Fill in order_id values for existing logs
             *
             * Note: We're ignoring split DBs (Assuming log and order will be in same), because that's necessarily
             * true for any version where the order_id column doesn't exist yet. so.
             */
            $order = $setup->getTable('sales_order');
            $log   = $setup->getTable('paradoxlabs_subscription_log');

            // Select order IDs for subscription orders
            $select = $setup->getConnection()->select()
                ->from($order, 'entity_id')
                ->columns(['increment_id'])
                ->joinInner($log, 'increment_id=sales_order.increment_id');

            // For each one, update log with order ID.
            $orderIdMap = $setup->getConnection()->fetchAll($select);
            foreach ($orderIdMap as $row) {
                $setup->getConnection()->update(
                    $log,
                    [
                        'order_id' => $row['entity_id']
                    ],
                    [
                        'order_increment_id=?' => $row['increment_id'],
                    ]
                );
            }
        }
    }
}
