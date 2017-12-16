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

namespace ParadoxLabs\Subscriptions\Observer;

/**
 * GuestCheckoutAvailableObserver Class
 */
class GuestCheckoutAvailableObserver implements \Magento\Framework\Event\ObserverInterface
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
     * Disable guest checkout when purchasing a subscription.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->helper->moduleIsActive() !== true) {
            return;
        }

        /** @var \Magento\Framework\DataObject $result */
        $result = $observer->getEvent()->getData('result');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote  = $observer->getEvent()->getData('quote');

        /**
         * If it's already inactive, don't care.
         */
        if ($result->getData('is_allowed') == false) {
            return;
        }

        /**
         * Otherwise, check if we have a subscription item. If so, not available.
         */
        if ($this->helper->quoteContainsSubscription($quote)) {
            $result->setData('is_allowed', false);
        }
    }
}
