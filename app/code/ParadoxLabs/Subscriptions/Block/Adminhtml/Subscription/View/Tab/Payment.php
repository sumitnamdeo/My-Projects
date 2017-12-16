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

namespace ParadoxLabs\Subscriptions\Block\Adminhtml\Subscription\View\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

/**
 * Payment tab
 */
class Payment extends Generic implements TabInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $tokenbaseHelper;

    /**
     * @var \Magento\Backend\Model\Url
     */
    protected $url;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Vault
     */
    protected $vaultHelper;

    /**
     * Payment tab constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $tokenbaseHelper
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Backend\Model\Url $url
     * @param \ParadoxLabs\Subscriptions\Helper\Vault $vaultHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \ParadoxLabs\TokenBase\Helper\Data $tokenbaseHelper,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Backend\Model\Url $url,
        \ParadoxLabs\Subscriptions\Helper\Vault $vaultHelper,
        array $data
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->tokenbaseHelper = $tokenbaseHelper;
        $this->url = $url;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->vaultHelper = $vaultHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Payment');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Payment');
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \ParadoxLabs\Subscriptions\Model\Subscription $subscription */
        $subscription = $this->_coreRegistry->registry('current_subscription');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $subscription->getQuote();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('payment_');

        $fieldset = $form->addFieldset('fieldset_payment', ['legend' => __('Payment Information')]);

        $fieldset->addField(
            'payment_note',
            'note',
            [
                'name'  => 'payment_note',
                'label' => __(''),
                'text'  => __(
                    'This payment record will be used for future payments. <b>Any changes will take effect on the next '
                    . 'billing.</b><br />To modify payment options, please go to the '
                    . '<a href="%1" target="_blank">customer profile</a>.',
                    $this->escapeUrl(
                        $this->url->getUrl('customer/index/edit', ['id' => $subscription->getCustomerId()])
                    )
                ),
            ]
        );

        $cardOptions = [];

        try {
            $activeCard = $this->vaultHelper->getQuoteCard($quote);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $fieldset->addField(
                'payment_error',
                'note',
                [
                    'name'  => 'payment_error',
                    'label' => __(''),
                    'text'  =>  __(
                        '<strong>This subscription has no assigned payment account. Please choose a payment account '
                        . 'below and save to prevent interruption.</strong>'
                    ),
                ]
            );

            $cardOptions[] = '';
        }

        /** @var \Magento\Vault\Api\Data\PaymentTokenInterface $card */
        $cards = $this->vaultHelper->getActiveCustomerCards($subscription->getCustomerId());
        foreach ($cards as $card) {
            $cardOptions[$card->getPublicHash()] = $this->vaultHelper->getCardLabel($card);
        }

        $fieldset->addField(
            'tokenbase_id',
            'select',
            [
                'name'     => 'tokenbase_id',
                'label'    => __('Payment Account'),
                'title'    => __('Payment Account'),
                'options'  => $cardOptions,
                'required' => true,
            ]
        );

        if (isset($activeCard) && $activeCard instanceof \Magento\Vault\Api\Data\PaymentTokenInterface) {
            $address = $this->getFormattedAddress($quote->getBillingAddress()->getDataModel());

            $fieldset->addField(
                'billing_address',
                'note',
                [
                    'name'  => 'billing_address',
                    'label' => __('Billing Address'),
                    'text'  => $address,
                    'note'  => __(
                        'Address corresponding to %1.',
                        $this->vaultHelper->getCardLabel($activeCard)
                    ),
                ]
            );

            $form->setValues([
                'tokenbase_id' => $activeCard->getPublicHash(),
            ]);
        }

        $this->setForm($form);

        $this->_eventManager->dispatch('adminhtml_subscription_view_tab_payment_prepare_form', ['form' => $form]);

        parent::_prepareForm();

        return $this;
    }

    /**
     * Get HTML-formatted card address. This is silly, but it's how the core says to do it.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param string $format
     * @return string
     * @see \Magento\Customer\Model\Address\AbstractAddress::format()
     */
    public function getFormattedAddress(\Magento\Customer\Api\Data\AddressInterface $address, $format = 'html')
    {
        /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
        $renderer = $this->addressConfig->getFormatByCode($format)->getRenderer();
        $addressData = $this->addressMapper->toFlatArray($address);

        return $renderer->renderArray($addressData);
    }
}
