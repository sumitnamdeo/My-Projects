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

namespace ParadoxLabs\Subscriptions\Helper;

/**
 * General helper
 */
class Data extends \ParadoxLabs\TokenBase\Helper\Operation
{
    /**
     * @var \Magento\Catalog\Helper\Product\Configuration
     */
    protected $productConfig;

    /**
     * @var array
     */
    protected $quoteContainsSubscription = [];

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Monolog\Logger $tokenbaseLogger
     * @param \Magento\Catalog\Helper\Product\Configuration $productConfig *Proxy
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Monolog\Logger $tokenbaseLogger,
        \Magento\Catalog\Helper\Product\Configuration $productConfig
    ) {
        parent::__construct($context, $tokenbaseLogger);

        $this->productConfig = $productConfig;
    }

    /**
     * Check whether the given item should be a subscription.
     *
     * @param \Magento\Framework\Model\AbstractExtensibleModel $item
     * @return bool
     */
    public function isItemSubscription(\Magento\Framework\Model\AbstractExtensibleModel $item)
    {
        /**
         * Check for enabled subscription and a chosen interval
         */
        /** @var \Magento\Sales\Model\Order\Item|\Magento\Quote\Model\Quote\Item $item */
        if ($item->getProduct()->getData('subscription_active') == 1
            && $this->getItemSubscriptionInterval($item) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get the subscription interval (if any) for the current item. 0 for none.
     *
     * @param \Magento\Framework\Model\AbstractExtensibleModel $item
     * @return int
     */
    public function getItemSubscriptionInterval(\Magento\Framework\Model\AbstractExtensibleModel $item)
    {
        /** @var \Magento\Sales\Model\Order\Item|\Magento\Quote\Model\Quote\Item $item */

        /**
         * Check for single-option case first.
         */
        if ($this->isSingleOptionSubscriptionItem($item)) {
            return (int)$item->getProduct()->getData('subscription_intervals');
        }

        /**
         * Check for chosen interval
         */
        if ($item instanceof \Magento\Quote\Model\Quote\Item) {
            $options = $this->productConfig->getCustomOptions($item);
        } else {
            $options = $item->getProductOptions();
            $options = isset($options['options']) ? $options['options'] : [];
        }

        if (is_array($options)) {
            foreach ($options as $option) {
                if ($option['label'] == $this->getSubscriptionLabel()) {
                    preg_match("/(\d+) /", $option['value'], $matches);

                    $oneString = (string)__('Every ' . $this->getItemSubscriptionUnit($item));

                    if (strpos($option['value'], $oneString) !== false) {
                        return 1;
                    } elseif (isset($matches[1]) && $matches[1] > 0) {
                        return (int)$matches[1];
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Determine whether the item's product has only one subscription option.
     *
     * @param \Magento\Framework\Model\AbstractExtensibleModel $item
     * @return bool
     */
    public function isSingleOptionSubscriptionItem(\Magento\Framework\Model\AbstractExtensibleModel $item)
    {
        /** @var \Magento\Sales\Model\Order\Item|\Magento\Quote\Model\Quote\Item $item */
        if ($item->getProduct()->getData('subscription_allow_onetime') == 0
            && strpos($item->getProduct()->getData('subscription_intervals'), ',') === false
            && $item->getProduct()->getData('subscription_intervals') != '') {
            return true;
        }

        return false;
    }

    /**
     * Get the subscription unit for the current item.
     *
     * @param \Magento\Framework\Model\AbstractExtensibleModel $item
     * @return string
     */
    public function getItemSubscriptionUnit(\Magento\Framework\Model\AbstractExtensibleModel $item)
    {
        /** @var \Magento\Sales\Model\Order\Item|\Magento\Quote\Model\Quote\Item $item */
        return $item->getProduct()->getData('subscription_unit');
    }

    /**
     * Get the subscription length for the current item--number of billing cycles to be run. 0 for indefinite.
     *
     * @param \Magento\Framework\Model\AbstractExtensibleModel $item
     * @return int
     */
    public function getItemSubscriptionLength(\Magento\Framework\Model\AbstractExtensibleModel $item)
    {
        /** @var \Magento\Sales\Model\Order\Item|\Magento\Quote\Model\Quote\Item $item */
        return (int)$item->getProduct()->getData('subscription_length');
    }

    /**
     * Get the subscription description for the given item.
     *
     * @param \Magento\Framework\Model\AbstractExtensibleModel $item
     * @return string
     */
    public function getItemSubscriptionDesc(\Magento\Framework\Model\AbstractExtensibleModel $item)
    {
        /** @var \Magento\Sales\Model\Order\Item|\Magento\Quote\Model\Quote\Item $item */
        return $item->getName();
    }

    /**
     * Calculate initial price for a subscription item.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return float
     */
    public function calculateInitialSubscriptionPrice(\Magento\Quote\Model\Quote\Item $item)
    {
        $product = $item->getProduct();
        $price   = $product->getFinalPrice();

        // Take subscription price to start (if any); otherwise, use normal product price.
        if ($product->getData('subscription_price') != '') {
            $price = max(0, (float)$product->getData('subscription_price'));
        }

        // Add the initial adjustment fee (if any)
        if ($product->getData('subscription_init_adjustment') != '') {
            $price = max(0, $price + (float)$product->getData('subscription_init_adjustment'));
        }

        return $price;
    }

    /**
     * Calculate regular price for a subscription item.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return float
     */
    public function calculateRegularSubscriptionPrice(\Magento\Quote\Model\Quote\Item $item)
    {
        $product = $item->getProduct();
        $price   = $product->getFinalPrice();

        // Take subscription price to start (if any); otherwise, use normal product price.
        if ($product->getData('subscription_price') != '') {
            $price = max(0, (float)$product->getData('subscription_price'));
        }

        return $price;
    }

    /**
     * Check whether the given quote contains a subscription item.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function quoteContainsSubscription($quote)
    {
        if (($quote instanceof \Magento\Quote\Api\Data\CartInterface) !== true) {
            return false;
        }

        if ($quote->getId() && isset($this->quoteContainsSubscription[$quote->getId()])) {
            return $this->quoteContainsSubscription[$quote->getId()];
        } else {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($quote->getAllItems() as $item) {
                if ($this->isItemSubscription($item) === true) {
                    if ($quote->getId()) {
                        $this->quoteContainsSubscription[$quote->getId()] = true;
                    }

                    return true;
                }
            }

            if ($quote->getId()) {
                $this->quoteContainsSubscription[$quote->getId()] = false;
            }
        }

        return false;
    }

    /**
     * Get label for the subscription custom option. Poor attempt at flexibility/localization.
     *
     * @return string
     */
    public function getSubscriptionLabel()
    {
        return (string)__(
            $this->scopeConfig->getValue(
                'subscriptions/general/option_label',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );
    }

    /**
     * Check whether custom option should be skipped if only a single option is available for a product.
     *
     * @return bool
     */
    public function skipSingleOption()
    {
        return $this->scopeConfig->getValue(
            'subscriptions/general/always_add_custom_option',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ? false : true;
    }

    /**
     * Check whether subscriptions module is enabled in configuration for the current scope.
     *
     * @return bool
     */
    public function moduleIsActive()
    {
        return $this->scopeConfig->getValue(
            'subscriptions/general/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ? true : false;
    }

    /**
     * Mark the quote as belonging to an existing subscription. Behavior can differ for initial vs. follow-up billings.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function setQuoteIsExistingSubscription(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        if ($quote->getPayment() instanceof \Magento\Quote\Api\Data\PaymentInterface) {
            $quote->getPayment()->setAdditionalInformation('is_subscription_generated', 1);
        }

        return $quote;
    }

    /**
     * Check whether the given quote is an existing subscription.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function isQuoteExistingSubscription(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        if ($quote->getPayment() instanceof \Magento\Quote\Api\Data\PaymentInterface) {
            return $quote->getPayment()->getAdditionalInformation('is_subscription_generated') == 1;
        }

        return false;
    }
}
