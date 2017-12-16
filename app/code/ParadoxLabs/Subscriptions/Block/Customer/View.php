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

namespace ParadoxLabs\Subscriptions\Block\Customer;

use Magento\Framework\View\Element\Template;

/**
 * View Class
 */
class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

    /**
     * @var \ParadoxLabs\Subscriptions\Model\Source\Period
     */
    protected $periodModel;

    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @var \ParadoxLabs\Subscriptions\Helper\Vault
     */
    protected $vaultHelper;

    /**
     * View constructor.
     *
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \ParadoxLabs\Subscriptions\Model\Source\Period $periodModel
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \ParadoxLabs\Subscriptions\Helper\Vault $vaultHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \ParadoxLabs\Subscriptions\Model\Source\Period $periodModel,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \ParadoxLabs\Subscriptions\Helper\Vault $vaultHelper,
        array $data
    ) {
        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->periodModel = $periodModel;
        $this->cardRepository = $cardRepository;
        $this->vaultHelper = $vaultHelper;
    }

    /**
     * Get current subscription model.
     *
     * @return \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface
     */
    public function getSubscription()
    {
        /** @var \ParadoxLabs\Subscriptions\Api\Data\SubscriptionInterface $subscription */
        $subscription = $this->registry->registry('current_subscription');

        return $subscription;
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
        $renderer    = $this->addressConfig->getFormatByCode($format)->getRenderer();
        $addressData = $this->addressMapper->toFlatArray($address);

        return $renderer->renderArray($addressData);
    }

    /**
     * Get frequency label (Runs every ___) for the current subscription.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getSubscriptionFrequencyLabel()
    {
        $count = $this->getSubscription()->getFrequencyCount();
        $unit  = $this->getSubscription()->getFrequencyUnit();

        if ($count > 1) {
            $unitLabel = $this->periodModel->getOptionTextPlural($unit);
        } else {
            $unitLabel = $this->periodModel->getOptionText($unit);
        }

        return __('%1 %2', $count, $unitLabel);
    }

    /**
     * Get the active card for the current subscription.
     *
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    public function getCard()
    {
        return $this->vaultHelper->getQuoteCard($this->getSubscription()->getQuote());
    }

    /**
     * Get subscription edit URL.
     *
     * @return string
     */
    public function getEditUrl()
    {
        return $this->_urlBuilder->getUrl('*/*/edit', ['id' => $this->getSubscription()->getId()]);
    }

    /**
     * Get text label for the given card.
     *
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $card
     * @return string
     */
    public function getCardLabel(\Magento\Vault\Api\Data\PaymentTokenInterface $card)
    {
        return $this->vaultHelper->getCardLabel($card);
    }

    /**
     * Get text label for the given card.
     *
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $card
     * @return string
     */
    public function getCardExpires(\Magento\Vault\Api\Data\PaymentTokenInterface $card)
    {
        return $this->vaultHelper->getCardExpires($card);
    }
}
