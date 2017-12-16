<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <magento@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\Subscriptions\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

/**
 * Uninstall Class
 */
class Uninstall implements \Magento\Framework\Setup\UninstallInterface
{
    /**
     * @var \Magento\Catalog\Setup\CategorySetupFactory
     */
    protected $categorySetupFactory;

    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Data
     */
    protected $helper;

    /**
     * Init
     *
     * @param \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory
     * @param \ParadoxLabs\Subscriptions\Helper\Data $helper
     */
    public function __construct(
        \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory,
        \ParadoxLabs\Subscriptions\Helper\Data $helper
    ) {
        $this->categorySetupFactory = $categorySetupFactory;
        $this->helper = $helper;
    }

    /**
     * Invoked when remove-data flag is set during module uninstall.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Catalog\Setup\CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);

        /**
         * Remove product attributes
         */
        $attributes    = [
            'subscription_active',
            'subscription_allow_onetime',
            'subscription_intervals',
            'subscription_unit',
            'subscription_length',
            'subscription_unit',
            'subscription_init_adjustment',
            'subscription_price',
        ];

        foreach ($attributes as $attribute) {
            try {
                $categorySetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, $attribute);
            } catch (\Exception $e) {
                $this->helper->log('subscriptions', (string)$e);
            }
        }

        /**
         * Remove product attribute group
         */
        try {
            $entityTypeId   = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
            $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

            $categorySetup->removeAttributeGroup(
                $entityTypeId,
                $attributeSetId,
                'Subscription'
            );
        } catch (\Exception $e) {
            $this->helper->log('subscriptions', (string)$e);
        }

        /**
         * Remove tables
         */
        try {
            $setup->getConnection()->dropTable(
                $setup->getTable('paradoxlabs_subscription')
            );

            $setup->getConnection()->dropTable(
                $setup->getTable('paradoxlabs_subscription_log')
            );
        } catch (\Exception $e) {
            $this->helper->log('subscriptions', (string)$e);
        }
    }
}
