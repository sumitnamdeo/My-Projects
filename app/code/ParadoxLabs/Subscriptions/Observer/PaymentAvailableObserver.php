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
 * PaymentAvailableObserver Class
 */
class PaymentAvailableObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Data
     */
    protected $helper;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $tokenbasehelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * GenerateSubscriptionsObserver constructor.
     *
     * @param \ParadoxLabs\Subscriptions\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Helper\Data $tokenbasehelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \ParadoxLabs\Subscriptions\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Helper\Data $tokenbasehelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->helper = $helper;
        $this->tokenbasehelper = $tokenbasehelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Disable ineligible payment methods when purchasing a subscription. Tokenbase methods only.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->helper->moduleIsActive() !== true) {
            return;
        }

        /** @var \Magento\Payment\Model\Method\AbstractMethod $method */
        $method = $observer->getEvent()->getData('method_instance');

        /** @var \Magento\Framework\DataObject $result */
        $result = $observer->getEvent()->getData('result');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote  = $observer->getEvent()->getData('quote');

        /**
         * If it's already inactive, don't care.
         */
        if ($result->getData('is_available') == false) {
            return;
        }

        /**
         * If it's a tokenbase method, we don't care any further.
         */
        if (in_array($method->getCode(), $this->tokenbasehelper->getActiveMethods())) {
            return;
        }

        /**
         * If it's a Vault method, don't care.
         */
        $vaultMethodActive = $this->scopeConfig->getValue(
            'payment/' . $method->getCode() . '_cc_vault/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->tokenbasehelper->getCurrentStoreId()
        );

        if ($vaultMethodActive == 1) {
            return;
        }

        /**
         * Otherwise, check if we have a subscription item. If so, not available.
         */
        if ($this->helper->quoteContainsSubscription($quote)) {
            $result->setData('is_available', false);
        }
    }
}
