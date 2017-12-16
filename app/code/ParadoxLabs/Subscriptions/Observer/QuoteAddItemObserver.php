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

namespace ParadoxLabs\Subscriptions\Observer;

/**
 * QuoteAddItemObserver Class
 */
class QuoteAddItemObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Data
     */
    protected $helper;

    /**
     * GenerateSubscriptionsObserver constructor.
     *
     * @param \ParadoxLabs\Subscriptions\Helper\Data $helper
     */
    public function __construct(
        \ParadoxLabs\Subscriptions\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Override item price et al when adding subscriptions to the cart.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->helper->moduleIsActive() !== true) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getData('quote_item');

        if ($this->helper->isItemSubscription($quoteItem) === true
            && $this->helper->isQuoteExistingSubscription($quoteItem->getQuote()) === false) {
            $price = $this->helper->calculateInitialSubscriptionPrice($quoteItem);

            if ($price != $quoteItem->getProduct()->getFinalPrice()) {
                $quoteItem->setOriginalCustomPrice($price);
            }
        }
    }
}
