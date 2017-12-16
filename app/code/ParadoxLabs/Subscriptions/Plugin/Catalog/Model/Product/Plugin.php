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

namespace ParadoxLabs\Subscriptions\Plugin\Catalog\Model\Product;

use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;

/**
 * \Magento\Catalog\Model\Product plugin
 */
class Plugin
{
    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Data
     */
    protected $helper;

    /**
     * @var ProductCustomOptionRepositoryInterface
     */
    protected $customOptionRepository;

    /**
     * @var \Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory
     */
    protected $customOptionFactory;

    /**
     * @var \Magento\Catalog\Api\Data\ProductCustomOptionValuesInterfaceFactory
     */
    protected $customOptionValueFactory;

    /**
     * @var \ParadoxLabs\Subscriptions\Model\Source\Period
     */
    protected $periodSource;

    /**
     * GenerateSubscriptionsObserver constructor.
     *
     * @param \ParadoxLabs\Subscriptions\Helper\Data $helper
     * @param ProductCustomOptionRepositoryInterface $customOptionRepository
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory $customOptionFactory
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionValuesInterfaceFactory $customOptionValueFactory
     * @param \ParadoxLabs\Subscriptions\Model\Source\Period $periodSource
     */
    public function __construct(
        \ParadoxLabs\Subscriptions\Helper\Data $helper,
        \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $customOptionRepository,
        \Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory $customOptionFactory,
        \Magento\Catalog\Api\Data\ProductCustomOptionValuesInterfaceFactory $customOptionValueFactory,
        \ParadoxLabs\Subscriptions\Model\Source\Period $periodSource
    ) {
        $this->helper = $helper;
        $this->customOptionRepository = $customOptionRepository;
        $this->customOptionFactory = $customOptionFactory;
        $this->customOptionValueFactory = $customOptionValueFactory;
        $this->periodSource = $periodSource;
    }

    /**
     * Before product save, process subscriptions custom options logic.
     *
     * We're using an around_ plugin here because beforeBeforeSave doesn't seem to work.
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    public function beforeBeforeSave(
        \Magento\Catalog\Model\Product $product
    ) {
        // Skip if duplicating existing product.
        if ($product->getData('is_duplicate')) {
            return;
        }

        // This catches new options, but not existing ones.
        $customOptions = $product->getProductOptions() ?: [];

        /**
         * Remove any subscription option that might exist
         */
        foreach ($customOptions as $key => $option) {
            if ($option['title'] == $this->helper->getSubscriptionLabel()) {
                $customOptions[$key]['is_delete'] = 1;
                $product->getOptionInstance()->addOption($customOptions[$key]);

                if (!isset($option['option_id'])) {
                    unset($customOptions[$key]);
                }
            }
        }

        // If you don't view the custom options tab, $customOptions will come out blank.
        // There's probably an easier workaround for this higher up the chain.
        // This catches existing options, but not new ones.
        /** @var \Magento\Catalog\Model\Product\Option $option */
        $options = $product->getOptions() ?: [];
        if ($options && !empty($options)) {
            foreach ($options as $option) {
                if ($option->getTitle() == $this->helper->getSubscriptionLabel()) {
                    $option->setData('is_delete', 1);
                    $this->customOptionRepository->delete($option);
                }
            }
        }

        // If not active, stop there.
        if ($this->helper->moduleIsActive() !== true
            || $product->getData('subscription_active') == 0
            || $product->getData('subscription_intervals') == ''
            || $this->skipSingleOption($product) === true) {
            $product->setData('product_options', $customOptions);
            return;
        }

        /**
         * Add subscription options if needed
         */
        $customOptions = $this->addCustomOption($product, $customOptions);

        $product->setCanSaveCustomOptions(true);
        $product->setData('product_options', $customOptions);

        /**
         * Build object form to keep 2.1+ happy
         */
        if (is_array($options) && !empty($customOptions)) {
            $options = $this->addOptionObject($product, $options, $customOptions);

            $product->setOptions($options);
        }
    }

    /**
     * Generate custom option for the given product subscription settings.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $customOptions
     * @return array
     */
    public function addCustomOption(\Magento\Catalog\Model\Product $product, $customOptions)
    {
        $intervals = $product->getData('subscription_intervals');

        if ($product->getData('subscription_allow_onetime') == 1) {
            $intervals = '0,' . $intervals;
        }

        $optionValues = [];
        $length = $product->getData('subscription_length');
        $unit = $product->getData('subscription_unit');
        $unitLabel = strtolower($this->periodSource->getOptionText($unit));
        $unitPlural = strtolower($this->periodSource->getOptionTextPlural($unit));
        $intervals = array_unique(
            array_map(
                'intval',
                explode(',', $intervals)
            )
        );

        foreach ($intervals as $k => $count) {
            if ($count == 0) {
                $title = __('One Time');
            } elseif ((int)$length > 0) {
                $real = (int)($count * $length);

                if ($count == 1) {
                    $title = __(
                        'Every ' . $unitLabel . ' for %1 ' . $unitPlural,
                        $real
                    );
                } else {
                    $title = __(
                        'Every %1 ' . $unitPlural . ' for %2 ' . $unitPlural,
                        $count,
                        $real
                    );
                }
            } else {
                if ($count == 1) {
                    $title = __('Every ' . $unitLabel);
                } else {
                    $title = __('Every %1 ' . $unitPlural, $count);
                }
            }

            $optionValue = [
                'title'      => $title,
                'sort_order' => $k,
                'price'      => 0,
                'price_type' => 'fixed',
            ];

            $optionValues[] = $optionValue;
        }

        $customOptions[] = [
            'title'      => $this->helper->getSubscriptionLabel(),
            'type'       => 'drop_down',
            'is_require' => 1,
            'is_delete'  => 0,
            'sort_order' => 1000,
            'values'     => $optionValues,
            'price'      => 0,
            'price_type' => 'fixed',
        ];

        return $customOptions;
    }

    /**
     * Check if we are allowed to skip a single option, and if so, if that's all the current product has.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function skipSingleOption(\Magento\Catalog\Model\Product $product)
    {
        if ($this->helper->skipSingleOption() === true
            && $product->getData('subscription_allow_onetime') == 0
            && strpos($product->getData('subscription_intervals'), ',') === false) {
            return true;
        }

        return false;
    }

    /**
     * Build an option object from the added custom option array.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface[] $options
     * @param array $customOptions
     * @return \Magento\Catalog\Api\Data\ProductCustomOptionInterface[]
     */
    protected function addOptionObject(
        \Magento\Catalog\Model\Product $product,
        $options,
        $customOptions
    ) {
        $subOption = array_pop($customOptions);

        $subValues = [];
        foreach ($subOption['values'] as $value) {
            $subValue = $this->customOptionValueFactory->create();
            $subValue->setTitle($value['title'])
                     ->setSortOrder($value['sort_order'])
                     ->setPrice($value['price'])
                     ->setPriceType($value['price_type']);

            $subValues[] = $subValue;
        }

        $subOption['values'] = $subValues;

        $option = $this->customOptionFactory->create();
        $option->addData($subOption);
        $option->setProduct($product);
        $option->setProductSku($product->getSku());

        // Unshift to ensure the first option is not deleted. Otherwise, we hit a logic problem in
        // product::beforeSave(), and product.has_options will always be false.
        array_unshift($options, $option);

        return $options;
    }
}
