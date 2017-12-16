<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\Subscriptions\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * UpgradeData Class
 */
class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * @var \Magento\Framework\Unserialize\Unserialize
     */
    protected $unserialize;

    /**
     * @var \Magento\Catalog\Setup\CategorySetupFactory
     */
    protected $categorySetupFactory;

    /**
     * UpgradeData constructor.
     *
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory
     * @param \ParadoxLabs\Subscriptions\Helper\Data $helper
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param \Magento\Framework\Unserialize\Unserialize $unserialize
     * @param \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \ParadoxLabs\Subscriptions\Helper\Data $helper,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Magento\Framework\Unserialize\Unserialize $unserialize,
        \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory
    ) {
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->helper = $helper;
        $this->productMetadata = $productMetadata;
        $this->unserialize = $unserialize;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * Data upgrade
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        /** @var \Magento\Catalog\Setup\CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);

        $quoteDb = $setup->getConnection('checkout');

        $magentoVersion = $this->productMetadata->getVersion();

        /**
         * Make sure that no subscription quote ever has a updated_at date in the past, or it's at risk of getting
         * pruned. Bye-bye subscription.
         *
         * We bypass the prune check via the updated_at date as such to ensure data persistence even if the module
         * should be temporarily disabled. A plugin doesn't ensure that.
         */
        $quotes = $setup->getTable('quote');
        $subs   = $setup->getTable('paradoxlabs_subscription');
        $quoteDb->query(
            "UPDATE {$quotes} SET updated_at='2038-01-01'
              WHERE entity_id IN (
                SELECT quote_id FROM {$subs} 
              ) AND updated_at<'2038-01-01'"
        );

        /**
         * Magento changed where Vault data is stored on payment records in 2.1.3, but we didn't. If Magento is already
         * on a newer version, fix any lingering incorrect data.
         */
        if (version_compare($context->getVersion(), '1.2.3', '<')
            && version_compare($magentoVersion, '2.1.3', '>=')) {
            $this->fixTokenMetadataStorage($setup, $context, $quoteDb, $magentoVersion);
        }

        /**
         * Add backend model to subscription_intervals product attribute for validation.
         */
        $this->addIntervalsBackendModel($setup, $context, $categorySetup);
    }

    /**
     * Unpack the given data (serialized/json).
     *
     * @param string $data
     * @param string $magentoVersion
     * @return mixed
     */
    protected function unpack($data, $magentoVersion)
    {
        if (version_compare($magentoVersion, '2.2.0', '<')) {
            return $this->unserialize->unserialize($data);
        } else {
            return json_decode($data, true);
        }
    }

    /**
     * Pack the given data into serialized string or JSON, depending on Magento version.
     *
     * @param mixed $data
     * @param string $magentoVersion
     * @return string
     */
    protected function pack($data, $magentoVersion)
    {
        if (version_compare($magentoVersion, '2.2.0', '<')) {
            return serialize($data);
        } else {
            return json_encode($data);
        }
    }

    /**
     * Magento changed where Vault data is stored on payment records in 2.1.3. Fix any lingering incorrect data.
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $quoteDb
     * @param string $magentoVersion
     * @return $this
     */
    public function fixTokenMetadataStorage(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context,
        \Magento\Framework\DB\Adapter\AdapterInterface $quoteDb,
        $magentoVersion
    ) {
        /**
         * Fix tokenbase_metadata in sales_order_payment
         */
        $salesDb = $setup->getConnection('sales');
        $select = $salesDb->select()
            ->from($setup->getTable('sales_order_payment'), 'entity_id')
            ->columns(['additional_information'])
            ->where('additional_information LIKE ?', '%token_metadata%');

        $items = $salesDb->fetchAll($select);
        foreach ($items as $item) {
            $additionalInfo = $this->unpack($item['additional_information'], $magentoVersion);
            $additionalInfo['customer_id'] = $additionalInfo['token_metadata']['customer_id'];
            $additionalInfo['public_hash'] = $additionalInfo['token_metadata']['public_hash'];
            unset($additionalInfo['token_metadata']);

            $salesDb->update(
                $setup->getTable('sales_order_payment'),
                ['additional_information' => $this->pack($additionalInfo, $magentoVersion)],
                ['entity_id = ?' => $item['entity_id']]
            );
        }

        /**
         * Fix tokenbase_metadata in quote_payment
         */
        $select = $quoteDb->select()
            ->from($setup->getTable('quote_payment'), 'payment_id')
            ->columns(['additional_information'])
            ->where('additional_information LIKE ?', '%token_metadata%');

        $items = $quoteDb->fetchAll($select);
        foreach ($items as $item) {
            $additionalInfo = $this->unpack($item['additional_information'], $magentoVersion);
            $additionalInfo['customer_id'] = $additionalInfo['token_metadata']['customer_id'];
            $additionalInfo['public_hash'] = $additionalInfo['token_metadata']['public_hash'];
            unset($additionalInfo['token_metadata']);

            $quoteDb->update(
                $setup->getTable('quote_payment'),
                ['additional_information' => $this->pack($additionalInfo, $magentoVersion)],
                ['payment_id = ?' => $item['payment_id']]
            );
        }

        return $this;
    }

    /**
     * Add backend model to subscription_intervals product attribute for validation.
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @param \Magento\Catalog\Setup\CategorySetup $categorySetup
     * @return $this
     */
    public function addIntervalsBackendModel(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context,
        \Magento\Catalog\Setup\CategorySetup $categorySetup
    ) {
        $intervalsAttr = $categorySetup->getAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'subscription_intervals'
        );

        if (empty($intervalsAttr['backend_model'])) {
            $categorySetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $intervalsAttr['attribute_id'],
                'backend_model',
                'ParadoxLabs\Subscriptions\Model\Attribute\Backend\Intervals'
            );
        }

        return $this;
    }
}
