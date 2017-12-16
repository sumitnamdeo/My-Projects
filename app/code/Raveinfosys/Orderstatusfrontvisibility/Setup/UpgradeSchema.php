<?php

namespace Raveinfosys\Orderstatusfrontvisibility\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $tableName = $setup->getTable('sales_order_status_state');
          if ($setup->getConnection()->isTableExists($tableName) == true) {
             $sql = "Update ".$tableName." Set visible_on_front = 1 where status = 'completed' AND state = 'complete'";
             $setup->getConnection()->query($sql);
        }
        $setup->endSetup();
    }
}