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

use \ParadoxLabs\Subscriptions\Model\Subscription;
use \ParadoxLabs\Subscriptions\Model\Source\Status;

/**
 * GenerateSubscriptionsObserver Class
 */
class GenerateSubscriptionsObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Data
     */
    protected $helper;

    /**
     * @var \ParadoxLabs\Subscriptions\Model\SubscriptionFactory
     */
    protected $subscriptionFactory;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Quote\Api\Data\AddressInterfaceFactory
     */
    protected $quoteAddressFactory;

    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    protected $objectCopyService;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Vault
     */
    protected $vaultHelper;

    /**
     * @var \ParadoxLabs\Subscriptions\Api\SubscriptionRepositoryInterface
     */
    protected $subscriptionRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $dateProcessor;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $emulator;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * GenerateSubscriptionsObserver constructor.
     *
     * @param \ParadoxLabs\Subscriptions\Helper\Data $helper
     * @param \ParadoxLabs\Subscriptions\Model\SubscriptionFactory $subscriptionFactory
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \Magento\Quote\Api\Data\AddressInterfaceFactory $quoteAddressFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \ParadoxLabs\Subscriptions\Helper\Vault $vaultHelper
     * @param \ParadoxLabs\Subscriptions\Api\SubscriptionRepositoryInterface $subscriptionRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateProcessor
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\App\Emulation $emulator
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     */
    public function __construct(
        \ParadoxLabs\Subscriptions\Helper\Data $helper,
        \ParadoxLabs\Subscriptions\Model\SubscriptionFactory $subscriptionFactory,
        \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory,
        \Magento\Quote\Api\Data\AddressInterfaceFactory $quoteAddressFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \ParadoxLabs\Subscriptions\Helper\Vault $vaultHelper,
        \ParadoxLabs\Subscriptions\Api\SubscriptionRepositoryInterface $subscriptionRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateProcessor,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\DataObject\Factory $dataObjectFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $emulator,
        \Magento\Framework\App\ProductMetadata $productMetadata
    ) {
        $this->helper = $helper;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->quoteFactory = $quoteFactory;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->customerRepository = $customerRepository;
        $this->objectCopyService = $objectCopyService;
        $this->vaultHelper = $vaultHelper;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->dateProcessor = $dateProcessor;
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->storeManager = $storeManager;
        $this->emulator = $emulator;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Create subscriptions as needed on order place.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->helper->moduleIsActive() !== true) {
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        // Ensure we don't end up generating new subscriptions from existing ones.
        if ($payment->getAdditionalInformation('is_subscription_generated') == 1) {
            return;
        }

        // If we are not in the correct scope, emulate it to ensure everything comes out correct.
        $emulate = ($this->storeManager->getStore()->getStoreId() != $order->getStoreId());
        if ($emulate === true) {
            $this->emulator->startEnvironmentEmulation($order->getStoreId());
        }

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            if ($this->helper->isItemSubscription($item) === true) {
                /**
                 * For each active subscription item,
                 * Create a matching quote
                 * Initialize an associated subscription
                 */

                try {
                    $subscription = $this->generateSubscription($order, $item);

                    $message = __(
                        'Subscription created. Initial order total: %1',
                        $order->formatPriceTxt($order->getGrandTotal())
                    );

                    $subscription->recordBilling($order, $message);

                    $this->subscriptionRepository->save($subscription);
                } catch (\Exception $e) {
                    $this->helper->log('subscriptions', (string)$e);

                    if ($emulate === true) {
                        $this->emulator->stopEnvironmentEmulation();
                    }

                    throw $e;
                }
            }
        }

        if ($emulate === true) {
            $this->emulator->stopEnvironmentEmulation();
        }
    }

    /**
     * Create a subscription for the given item.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @return Subscription
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generateSubscription(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Api\Data\OrderItemInterface $item
    ) {
        /** @var \Magento\Sales\Model\Order\Item $item */

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->generateSubscriptionQuote($order, $item);

        /** @var Subscription $subscription */
        $subscription = $this->subscriptionFactory->create();

        $subscription->setStoreId($quote->getStoreId());
        $subscription->setStatus(Status::STATUS_ACTIVE);
        $subscription->setCustomerId($quote->getCustomerId());
        $subscription->setQuote($quote);
        $subscription->setFrequencyCount($this->helper->getItemSubscriptionInterval($item));
        $subscription->setFrequencyUnit($this->helper->getItemSubscriptionUnit($item));
        $subscription->setLength($this->helper->getItemSubscriptionLength($item));
        $subscription->setDescription($this->helper->getItemSubscriptionDesc($item));
        $subscription->setSubtotal($quote->getBaseSubtotal());
        $subscription->calculateNextRun();

        $subscription->addRelatedObject($quote, true);

        return $subscription;
    }

    /**
     * Create a subscription base quote for the given item.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generateSubscriptionQuote(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Api\Data\OrderItemInterface $item
    ) {
        /**
         * Initialize objects
         */

        /** @var \Magento\Sales\Model\Order\Item $item */
        /** @var \Magento\Sales\Model\Order $order */

        $orderQuote = $this->cartRepository->get($order->getQuoteId());

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();

        /**
         * Duplicate billing address
         */

        /** @var \Magento\Quote\Model\Quote\Address $billingAddress */
        $billingAddress = $this->quoteAddressFactory->create();

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_copy_order_billing_address',
            'to_order',
            $orderQuote->getBillingAddress(),
            $billingAddress
        );

        $billingAddress->setCustomerId($orderQuote->getCustomerId());
        $billingAddress->setEmail($orderQuote->getCustomerEmail());

        /**
         * Duplicate shipping address
         */

        /** @var \Magento\Quote\Model\Quote\Address $shippingAddress */
        $shippingAddress = $this->quoteAddressFactory->create();

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_copy_order_shipping_address',
            'to_order',
            $orderQuote->getShippingAddress(),
            $shippingAddress
        );

        $shippingAddress->setCustomerId($orderQuote->getCustomerId());
        $shippingAddress->setEmail($orderQuote->getCustomerEmail());

        /**
         * Duplicate payment object
         */

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_order_payment',
            'to_quote_payment',
            $order->getPayment(),
            $quote->getPayment()
        );

        $quote->getPayment()->setId(null);
        $quote->getPayment()->setQuoteId(null);

        // Record the quote/order source to prevent a generation loop
        $this->helper->setQuoteIsExistingSubscription($quote);

        $this->prepareVaultData($quote, $order);

        /**
         * Duplicate customer info
         */
        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_order_customer',
            'to_quote',
            $order,
            $quote
        );

        // Try to load and set customer.
        $customerId = $order->getCustomerId();

        if ($customerId > 0) {
            try {
                $customer = $this->customerRepository->getById($customerId);

                $quote->assignCustomer($customer);
            } catch (\Exception $e) {
                // Ignore missing customer error
            }
        }

        /**
         * Pull quote together
         */

        // Set a far-off quote updated date to avoid pruning. This is the highest Magento allows (timestamp).
        $updatedAt = $this->dateProcessor->date('2038-01-01', null, false);

        $quote->setStoreId($order->getStoreId())
              ->setIsMultiShipping(false)
              ->setIsActive(false)
              ->setUpdatedAt($updatedAt->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT))
              ->setRemoteIp($order->getRemoteIp())
              ->setBillingAddress($billingAddress)
              ->setShippingAddress($shippingAddress);

        $product = $item->getProduct();

        if (!$product->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not find product for item %1 (%2)', $item->getSku(), $item->getId())
            );
        }

        /**
         * Set the product and price
         */
        $info = $item->getProductOptionByCode('info_buyRequest');
        $info = $this->dataObjectFactory->create($info);
        $info->setData('qty', $item->getQtyOrdered());

        $quote->addProduct($product, $info);

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $quote->getItemsCollection()->getFirstItem();

        $newPrice = $this->helper->calculateRegularSubscriptionPrice($quoteItem);

        if ($newPrice != $product->getFinalPrice()) {
            $quoteItem->setOriginalCustomPrice($newPrice);
        }

        /**
         * Set shipping info
         */

        $quote->setIsVirtual($quote->getIsVirtual());

        $quote->getShippingAddress()->setCollectShippingRates(true)
                                    ->collectShippingRates();

        $quote->getShippingAddress()->setShippingMethod($order->getShippingMethod())
                                    ->setShippingDescription($order->getShippingDescription());

        $quote->collectTotals();

        return $quote;
    }

    /**
     * If the payment method is not TokenBase, convert it to its proper vault form for later.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareVaultData(
        \Magento\Quote\Api\Data\CartInterface $quote,
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        /** @var \Magento\Sales\Model\Order $order */

        if ($this->vaultHelper->isQuoteTokenBase($quote) === false) {
            $payment = $quote->getPayment();
            $method = $payment->getMethod();

            if (strpos($method, '_cc_vault') === false) {
                $payment->setMethod($method . '_cc_vault');
            }

            // token_metadata was used in 2.1.0-2.1.2. In 2.1.3 the values were moved to the top level.
            $metadata = $payment->getAdditionalInformation('token_metadata')
                ?: $payment->getAdditionalInformation();

            if ($metadata === null || !isset($metadata['public_hash']) || empty($metadata['public_hash'])) {
                // We're missing the vault info. Fetch and store it.

                if ($order->getPayment()->getId() === null) {
                    // The order must be saved to trigger vault hash generation.
                    // @see \Magento\Vault\Observer\AfterPaymentSaveObserver::execute()
                    $this->orderRepository->save($order);
                }

                $card = $this->getVaultExtension($order->getPayment()->getExtensionAttributes());

                if ($card !== null) {
                    // Store the data at the appropriate spot.
                    if (version_compare($this->productMetadata->getVersion(), '2.1.3', '>=')) {
                        $payment->setAdditionalInformation('customer_id', $card->getCustomerId());
                        $payment->setAdditionalInformation('public_hash', $card->getPublicHash());
                    } else {
                        $payment->setAdditionalInformation(
                            'token_metadata',
                            [
                                'customer_id' => $card->getCustomerId(),
                                'public_hash' => $card->getPublicHash(),
                            ]
                        );
                    }
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Could not find payment card.')
                    );
                }
            }

            // Set Vault card payment info on the quote.
            $card = $this->vaultHelper->getQuoteCard($quote);
            $expires = strtotime($this->vaultHelper->getCardExpires($card));
            $payment->setData('cc_type', $this->vaultHelper->getCardType($card));
            $payment->setData('cc_last_4', $this->vaultHelper->getCardLast4($card));
            $payment->setData('cc_exp_year', date('Y', $expires));
            $payment->setData('cc_exp_month', date('m', $expires));
        }

        return $this;
    }

    /**
     * Get the Vault order payment extension (Vault card), if any.
     *
     * @param \Magento\Sales\Api\Data\OrderPaymentExtensionInterface|null $extensionAttributes
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface|null
     */
    protected function getVaultExtension(
        \Magento\Sales\Api\Data\OrderPaymentExtensionInterface $extensionAttributes = null
    ) {
        if ($extensionAttributes === null) {
            return null;
        }

        $card = $extensionAttributes->getVaultPaymentToken();
        if ($card === null || empty($card->getGatewayToken())) {
            return null;
        }

        return $card;
    }
}
