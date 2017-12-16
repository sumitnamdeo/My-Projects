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

namespace ParadoxLabs\Subscriptions\Model\Service;

use Magento\Customer\Api\AddressRepositoryInterface;

/**
 * Subscription service model: Common actions to be performed on subscriptions.
 *
 * @api
 */
class Subscription
{
    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    protected $objectCopyService;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Quote\Api\Data\AddressInterfaceFactory
     */
    protected $quoteAddressFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var AddressRepositoryInterface
     */
    protected $customerAddressRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;
    
    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Data
     */
    protected $helper;

    /**
     * @var \ParadoxLabs\Subscriptions\Model\Service\EmailSender
     */
    protected $emailSender;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $emulator;

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
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * Subscription service constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement *Proxy
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \Magento\Quote\Api\Data\AddressInterfaceFactory $quoteAddressFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param AddressRepositoryInterface $customerAddressRepository
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender *Proxy
     * @param \Psr\Log\LoggerInterface $logger
     * @param \ParadoxLabs\Subscriptions\Helper\Data $helper
     * @param \ParadoxLabs\Subscriptions\Model\Service\EmailSender $emailSender *Proxy
     * @param \Magento\Store\Model\App\Emulation $emulator
     * @param \ParadoxLabs\Subscriptions\Helper\Vault $vaultHelper
     * @param \ParadoxLabs\Subscriptions\Api\SubscriptionRepositoryInterface $subscriptionRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateProcessor
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory,
        \Magento\Quote\Api\Data\AddressInterfaceFactory $quoteAddressFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $customerAddressRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Psr\Log\LoggerInterface $logger,
        \ParadoxLabs\Subscriptions\Helper\Data $helper,
        \ParadoxLabs\Subscriptions\Model\Service\EmailSender $emailSender,
        \Magento\Store\Model\App\Emulation $emulator,
        \ParadoxLabs\Subscriptions\Helper\Vault $vaultHelper,
        \ParadoxLabs\Subscriptions\Api\SubscriptionRepositoryInterface $subscriptionRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateProcessor,
        \Magento\Framework\App\ProductMetadata $productMetadata
    ) {
        $this->registry = $registry;
        $this->objectCopyService = $objectCopyService;
        $this->cardRepository = $cardRepository;
        $this->quoteRepository = $cartRepository;
        $this->quoteManagement = $quoteManagement;
        $this->quoteFactory = $quoteFactory;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->customerRepository = $customerRepository;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->eventManager = $eventManager;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->emailSender = $emailSender;
        $this->emulator = $emulator;
        $this->vaultHelper = $vaultHelper;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->dateProcessor = $dateProcessor;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Change subscription payment account to the given card.
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription
     * @param string $hash Card hash owned by the subscription customer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function changePaymentId(
        \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription,
        $hash
    ) {
        /** @var \ParadoxLabs\Subscriptions\Model\Subscription $subscription */

        $card = $this->vaultHelper->getCardByHash($hash);

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $subscription->getQuote();

        if ($card->getId() != $quote->getPayment()->getData('tokenbase_id')) {
            if ($card->getId() > 0 && $card->getCustomerId() == $subscription->getCustomerId()) {
                $payment = $quote->getPayment();

                if ($card instanceof \ParadoxLabs\TokenBase\Api\Data\CardInterface) {
                    /**
                     * Update billing address from the given card. Only known for TokenBase.
                     */
                    $this->objectCopyService->copyFieldsetToTarget(
                        'sales_copy_order_billing_address',
                        'to_order',
                        $card->getAddress(),
                        $quote->getBillingAddress()
                    );

                    $payment->setMethod($card->getPaymentMethodCode());
                    $payment->setData('tokenbase_id', $card->getId());
                } else {
                    /**
                     * Update payment data.
                     *
                     * token_metadata was used in 2.1.0-2.1.2. In 2.1.3 the values were moved to the top level.
                     */
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

                    $payment->setMethod($card->getPaymentMethodCode() . '_cc_vault');
                    $payment->setData('tokenbase_id', null);

                    $expires = strtotime($this->vaultHelper->getCardExpires($card));
                    $payment->setData('cc_type', $this->vaultHelper->getCardType($card));
                    $payment->setData('cc_last_4', $this->vaultHelper->getCardLast4($card));
                    $payment->setData('cc_exp_year', date('Y', $expires));
                    $payment->setData('cc_exp_month', date('m', $expires));
                }

                $subscription->addRelatedObject($quote, true);

                $subscription->addLog(
                    __('Payment method changed to %1.', $this->vaultHelper->getCardLabel($card))
                );
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Invalid payment ID.')
                );
            }
        }

        return $this;
    }

    /**
     * Change subscription shipping address to the given data.
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription
     * @param array $data Array of address info
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function changeShippingAddress(
        \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription,
        $data
    ) {
        /** @var \ParadoxLabs\Subscriptions\Model\Subscription $subscription */

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $subscription->getQuote();

        if (isset($data['address_id']) && $data['address_id'] > 0 && $subscription->getCustomerId() > 0) {
            $customer = $this->customerRepository->getById($subscription->getCustomerId());

            if ($customer->getId() != $subscription->getCustomerId()) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __('Unable to load subscription customer.')
                );
            }

            $address = $this->customerAddressRepository->getById($data['address_id']);

            if ($address instanceof \Magento\Customer\Api\Data\AddressInterface
                && $address->getId() == $data['address_id']
                && $address->getCustomerId() == $customer->getId()) {
                $source = $address;
            } else {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __('Please choose a valid shipping address.')
                );
            }
        } else {
            $source = $data;
        }

        $shippingAddress = $quote->getShippingAddress();

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_customer_address',
            'to_quote_address',
            $source,
            $shippingAddress
        );

        $data = $shippingAddress->getData();
        foreach ($data as $key => $value) {
            if ($key !== 'region' && !is_object($value) && $shippingAddress->getOrigData($key) != $value) {
                $shippingAddress->validate();

                $subscription->addLog(
                    __('Shipping address changed.')
                );

                $subscription->addRelatedObject($quote, true);

                break;
            }
        }

        return $this;
    }

    /**
     * Generate a hash from fulfillment details (billing, shipping, payment) for the given subscription.
     *
     * Used for identifying subscriptions that can be merged and billed together.
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription
     * @return string
     */
    public function hashFulfillmentInfo(\ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription)
    {
        $keys  = [];

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $subscription->getQuote();

        // Customer
        $keys[] = $subscription->getCustomerId();

        // Store
        $keys[] = $subscription->getStoreId();

        // Payment
        $keys[] = $quote->getPayment()->getData('tokenbase_id');

        // Shipping
        if ((bool)$quote->getIsVirtual() === false) {
            $shippingAddr = $quote->getShippingAddress();
            $shippingKeys = [
                'shipping_method',
                'street',
                'city',
                'region',
                'region_id',
                'postcode',
                'country_id',
            ];

            foreach ($shippingKeys as $key) {
                $keys[] = $shippingAddr->getData($key);
            }
        }

        return md5(implode('-', $keys));
    }

    /**
     * Generate order for the given subscription(s). If multiple given, they should all share the same
     * payment and shipping info.
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface[] $subscriptions
     * @return bool Success
     */
    public function generateOrder($subscriptions)
    {
        /**
         * This wrapper function manages error handling and emulation.
         */

        /** @var \ParadoxLabs\Subscriptions\Model\Subscription $firstSubscription */
        $firstSubscription = current($subscriptions);

        $this->emulator->startEnvironmentEmulation($firstSubscription->getStoreId());

        try {
            foreach ($subscriptions as $subscription) {
                if ($subscription->getStatus() != \ParadoxLabs\Subscriptions\Model\Source\Status::STATUS_ACTIVE) {
                    throw new \Magento\Framework\Exception\StateException(
                        __(
                            "Subscriptions may only be billed in the '%1' status. #%2 has status '%3'.",
                            \ParadoxLabs\Subscriptions\Model\Source\Status::STATUS_ACTIVE,
                            $subscription->getId(),
                            $subscription->getStatus()
                        )
                    );
                }
            }

            $this->generateOrderInternal($subscriptions);

            $this->emulator->stopEnvironmentEmulation();

            return true;
        } catch (\Exception $e) {
            $this->handleSubscriptionsError($subscriptions, $e);
        }

        $this->emulator->stopEnvironmentEmulation();

        return false;
    }

    /**
     * Generate order for the given subscription(s). If multiple given, they should all share the same
     * payment and shipping info.
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface[] $subscriptions
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generateOrderInternal($subscriptions)
    {
        /**
         * Initialize quote from first subscription
         */
        $quote = $this->generateBillingQuote(
            current($subscriptions)
        );

        /**
         * Add item(s) from each subscription
         */
        /** @var \ParadoxLabs\Subscriptions\Model\Subscription $subscription */
        foreach ($subscriptions as $subscription) {
            /** @var \Magento\Quote\Model\Quote $subscriptionQuote */
            $subscriptionQuote = $subscription->getQuote();

            $quote->merge($subscriptionQuote);
        }

        /**
         * Calculate shipping and totals
         */
        $quote->setIsVirtual($quote->getIsVirtual());

        // Unfortunately, it's necessary to collectTotals in two steps. This one preps for any amount-based shipping
        // settings or promotion rules.
        $quote->collectTotals();

        // This fetches current shipping rates. NB: The rates available now may differ from the original order,
        // especially if minimum amounts are in play! This can be a problem.
        $quote->getShippingAddress()->setCollectShippingRates(true)
              ->collectShippingRates();

        // This second collectTotals pulls in the new shipping amount.
        $quote->setTotalsCollectedFlag(false)
              ->collectTotals();

        /**
         * Run the order
         */
        $this->eventManager->dispatch(
            'paradoxlabs_subscription_generate_before',
            [
                'quote'         => $quote,
                'subscriptions' => $subscriptions
            ]
        );

        // This event allows for soft dependencies on payment methods (module won't be referenced unless used).
        $this->eventManager->dispatch(
            'paradoxlabs_subscription_prepare_payment_' . $quote->getPayment()->getMethod(),
            [
                'quote'         => $quote,
                'subscriptions' => $subscriptions
            ]
        );

        $this->quoteRepository->save($quote);

        $ids = [];
        foreach ($subscriptions as $subscription) {
            $ids[] = $subscription->getId();
        }

        $this->helper->log(
            'subscriptions',
            __('Placing generateOrderInternal([%1])', implode(',', $ids))
        );

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->quoteManagement->submit($quote);

        if (!($order instanceof \Magento\Sales\Api\Data\OrderInterface)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Failed to place order.')
            );
        }

        /**
         * Update post-order
         */
        $message = __(
            'Subscription billed. Order total: %1',
            $order->formatPriceTxt($order->getGrandTotal())
        );

        foreach ($subscriptions as $subscription) {
            $subscription->recordBilling($order, $message);
            $subscription->calculateNextRun();
        }

        $this->eventManager->dispatch(
            'paradoxlabs_subscription_generate_after',
            [
                'order'         => $order,
                'quote'         => $quote,
                'subscriptions' => $subscriptions
            ]
        );

        foreach ($subscriptions as $subscription) {
            $this->subscriptionRepository->save($subscription);
        }

        /**
         * Send email
         */
        if ($order->getCanSendNewEmailFlag()) {
            try {
                $this->orderSender->send($order);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        return $this;
    }

    /**
     * Generate a new quote from the given subscription info.
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription
     * @return \Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateBillingQuote(\ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription)
    {
        /**
         * Initialize objects
         */

        /** @var \Magento\Quote\Model\Quote $sourceQuote */
        $sourceQuote = $subscription->getQuote();

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
            $sourceQuote->getBillingAddress(),
            $billingAddress
        );

        $billingAddress->setCustomerId($sourceQuote->getBillingAddress()->getCustomerId());

        /**
         * Duplicate shipping address
         */

        /** @var \Magento\Quote\Model\Quote\Address $shippingAddress */
        $shippingAddress = $this->quoteAddressFactory->create();

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_copy_order_shipping_address',
            'to_order',
            $sourceQuote->getShippingAddress(),
            $shippingAddress
        );

        $shippingAddress->setShippingMethod($sourceQuote->getShippingAddress()->getShippingMethod())
                        ->setShippingDescription($sourceQuote->getShippingAddress()->getShippingDescription())
                        ->setCustomerId($sourceQuote->getShippingAddress()->getCustomerId());

        /**
         * Duplicate payment object
         */

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_order_payment',
            'to_quote_payment',
            $sourceQuote->getPayment(),
            $quote->getPayment()
        );

        $quote->getPayment()->setId(null);
        $quote->getPayment()->setQuoteId(null);

        // Record the quote/order source to prevent a generation loop
        $this->helper->setQuoteIsExistingSubscription($quote);

        /**
         * Duplicate customer info
         */
        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_quote_customer',
            'to_quote',
            $sourceQuote,
            $quote
        );

        // Try to load and set customer.
        $customerId = $subscription->getCustomerId();

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

        $now = $this->dateProcessor->date(null, null, false);

        $quote->setStoreId($sourceQuote->getStoreId())
              ->setIsMultiShipping(false)
              ->setIsActive(false)
              ->setUpdatedAt($now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT))
              ->setRemoteIp($sourceQuote->getRemoteIp())
              ->setBillingAddress($billingAddress)
              ->setShippingAddress($shippingAddress);

        return $quote;
    }

    /**
     * Handle exceptions from the subscription generation process.
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface[] $subscriptions
     * @param \Exception $exception
     * @return $this
     */
    protected function handleSubscriptionsError($subscriptions, \Exception $exception)
    {
        try {
            $ids = [];
            foreach ($subscriptions as $subscription) {
                $ids[] = $subscription->getId();
            }

            $this->helper->log(
                'subscriptions',
                __('Error on generateOrder([%1]): %2', implode(',', $ids), $exception->getMessage())
            );

            $this->eventManager->dispatch(
                'paradoxlabs_subscription_billing_failed',
                [
                    'subscriptions' => $subscriptions,
                    'exception'     => $exception,
                ]
            );

            if ($exception instanceof \Magento\Framework\Exception\PaymentException) {
                $this->changeSubscriptionsStatus(
                    $subscriptions,
                    \ParadoxLabs\Subscriptions\Model\Source\Status::STATUS_PAYMENT_FAILED,
                    (string)__('ERROR: %1', $exception->getMessage())
                );

                $this->sendPaymentFailedEmail($subscriptions, $exception);
            } else {
                $this->changeSubscriptionsStatus(
                    $subscriptions,
                    \ParadoxLabs\Subscriptions\Model\Source\Status::STATUS_PAUSED,
                    (string)__('ERROR: %1', $exception->getMessage())
                );
            }

            $this->sendBillingFailedEmail($subscriptions, $exception);
        } catch (\Exception $e) {
            $this->helper->log(
                'subscriptions',
                __('Error while handling "%1": %2', $exception->getMessage(), (string)$e)
            );
        }

        return $this;
    }

    /**
     * Send billing failure email to admin
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface[] $subscriptions
     * @param \Exception $exception
     * @return $this
     */
    public function sendBillingFailedEmail($subscriptions, \Exception $exception)
    {
        foreach ($subscriptions as $subscription) {
            $this->emailSender->sendBillingFailedEmail($subscription, $exception->getMessage());
        }

        return $this;
    }

    /**
     * Send payment failure email to customer
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface[] $subscriptions
     * @param \Exception $exception
     * @return $this
     */
    public function sendPaymentFailedEmail($subscriptions, \Exception $exception)
    {
        foreach ($subscriptions as $subscription) {
            $this->emailSender->sendPaymentFailedEmail($subscription, $exception->getMessage());
        }

        return $this;
    }

    /**
     * Set status for the given subscriptions, and log the change.
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface[] $subscriptions
     * @param string $status
     * @param null $message
     * @return $this
     */
    public function changeSubscriptionsStatus($subscriptions, $status, $message = null)
    {
        foreach ($subscriptions as $subscription) {
            $subscription->setStatus($status, $message);
            $this->subscriptionRepository->save($subscription);
        }

        return $this;
    }
}
