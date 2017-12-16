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

/**
 * EmailSender Class
 */
class EmailSender
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * @var \Magento\Directory\Model\Currency[]
     */
    protected $currencies = [];

    /**
     * EmailSender constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->currencyFactory = $currencyFactory;
    }

    /**
     * Send billing failure email to admin
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription
     * @param string $message
     * @return $this
     */
    public function sendBillingFailedEmail(
        \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription,
        $message
    ) {
        $active = $this->scopeConfig->getValue(
            'subscriptions/billing_failed/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $subscription->getStoreId()
        );

        if ($active != 1) {
            return $this;
        }

        $paymentFailed = false;
        if ($subscription->getStatus() == \ParadoxLabs\Subscriptions\Model\Source\Status::STATUS_PAYMENT_FAILED) {
            $paymentFailed = true;
        }
        
        $paymentFailedActive = $this->scopeConfig->getValue(
            'subscriptions/payment_failed/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $subscription->getStoreId()
        );

        $this->inlineTranslation->suspend();

        $template = $this->scopeConfig->getValue(
            'subscriptions/billing_failed/template',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $subscription->getStoreId()
        );

        $copyTo = $this->getEmails('subscriptions/billing_failed/copy_to', $subscription->getStoreId());
        $copyMethod = $this->scopeConfig->getValue(
            'subscriptions/billing_failed/copy_method',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $subscription->getStoreId()
        );
        $bcc = [];
        if ($copyTo && $copyMethod == 'bcc') {
            $bcc = $copyTo;
        }

        $_receiver = $this->scopeConfig->getValue(
            'subscriptions/billing_failed/receiver',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $subscription->getStoreId()
        );
        $sendTo = [
            [
                'email' => $this->scopeConfig->getValue(
                    'trans_email/ident_' . $_receiver . '/email',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $subscription->getStoreId()
                ),
                'name'  => $this->scopeConfig->getValue(
                    'trans_email/ident_' . $_receiver . '/name',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $subscription->getStoreId()
                ),
            ],
        ];

        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = ['email' => $email, 'name' => null];
            }
        }

        foreach ($sendTo as $recipient) {
            $transport = $this->transportBuilder->setTemplateIdentifier(
                $template
            )->setTemplateOptions(
                [
                    'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $subscription->getStoreId(),
                ]
            )->setTemplateVars(
                [
                    'subscription'    => $subscription,
                    'subtotal'        => $this->getFormattedSubtotal($subscription),
                    'reason'          => $message,
                    'paymentFailure'  => $paymentFailed === true && $paymentFailedActive == 1,
                ]
            )->setFrom(
                $this->scopeConfig->getValue(
                    'subscriptions/billing_failed/identity',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $subscription->getStoreId()
                )
            )->addTo(
                $recipient['email'],
                $recipient['name']
            )->addBcc(
                $bcc
            )->getTransport();

            $transport->sendMessage();
        }

        $this->inlineTranslation->resume();

        return $this;
    }

    /**
     * Send payment failure email to customer
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription
     * @param string $message
     * @return $this
     */
    public function sendPaymentFailedEmail(
        \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription,
        $message
    ) {
        $active = $this->scopeConfig->getValue(
            'subscriptions/payment_failed/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $subscription->getStoreId()
        );

        if ($active != 1) {
            return $this;
        }

        $this->inlineTranslation->suspend();

        $template = $this->scopeConfig->getValue(
            'subscriptions/payment_failed/template',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $subscription->getStoreId()
        );

        $copyTo = $this->getEmails('subscriptions/payment_failed/copy_to', $subscription->getStoreId());
        $copyMethod = $this->scopeConfig->getValue(
            'subscriptions/payment_failed/copy_method',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $subscription->getStoreId()
        );
        $bcc = [];
        if ($copyTo && $copyMethod == 'bcc') {
            $bcc = $copyTo;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $subscription->getQuote();

        $sendTo = [
            [
                'email' => $quote->getCustomerEmail(),
                'name'  => $quote->getCustomerFirstname() . ' ' . $quote->getCustomerLastname(),
            ],
        ];

        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = ['email' => $email, 'name' => null];
            }
        }

        foreach ($sendTo as $recipient) {
            $transport = $this->transportBuilder->setTemplateIdentifier(
                $template
            )->setTemplateOptions(
                [
                    'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $subscription->getStoreId(),
                ]
            )->setTemplateVars(
                [
                    'subscription'    => $subscription,
                    'subtotal'        => $this->getFormattedSubtotal($subscription),
                    'reason'          => $message,
                ]
            )->setFrom(
                $this->scopeConfig->getValue(
                    'subscriptions/payment_failed/identity',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $subscription->getStoreId()
                )
            )->addTo(
                $recipient['email'],
                $recipient['name']
            )->addBcc(
                $bcc
            )->getTransport();

            $transport->sendMessage();
        }

        $this->inlineTranslation->resume();

        return $this;
    }

    /**
     * Get email addresses from the given config path.
     *
     * @param string $configPath
     * @param null|string|bool|int|\Magento\Store\Model\Store $storeId
     * @return array|false
     */
    protected function getEmails($configPath, $storeId)
    {
        $data = $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!empty($data)) {
            return explode(',', $data);
        }

        return false;
    }

    /**
     * Get the formatted subscription subtotal.
     *
     * @param \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription
     * @return string
     */
    public function getFormattedSubtotal(\ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription)
    {
        /** @var \ParadoxLabs\Subscriptions\Model\Subscription $subscription */
        $currency = $subscription->getData('quote_currency_code');

        if (!isset($this->currencies[$currency])) {
            $this->currencies[$currency] = $this->currencyFactory->create();
            $this->currencies[$currency]->load($currency);
        }

        return $this->currencies[$currency]->formatTxt($subscription->getSubtotal());
    }
}
