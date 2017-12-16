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
 * Edit Class
 */
class Edit extends View
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $tokenbaseHelper;

    /**
     * Edit constructor.
     *
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \ParadoxLabs\Subscriptions\Model\Source\Period $periodModel
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \ParadoxLabs\Subscriptions\Helper\Vault $vaultHelper
     * @param \ParadoxLabs\TokenBase\Helper\Data $tokenbaseHelper
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
        \ParadoxLabs\TokenBase\Helper\Data $tokenbaseHelper,
        array $data
    ) {
        parent::__construct(
            $context,
            $registry,
            $addressMapper,
            $addressConfig,
            $periodModel,
            $cardRepository,
            $vaultHelper,
            $data
        );

        $this->tokenbaseHelper = $tokenbaseHelper;
    }

    /**
     * Get subscription save URL.
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->_urlBuilder->getUrl('*/*/editPost', ['id' => $this->getSubscription()->getId()]);
    }

    /**
     * Get subscription view URL.
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->_urlBuilder->getUrl('*/*/view', ['id' => $this->getSubscription()->getId()]);
    }

    /**
     * Get active customer cards.
     *
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface[]
     */
    public function getCustomerCards()
    {
        return $this->vaultHelper->getActiveCustomerCards();
    }

    /**
     * Get TokenBase helper class
     *
     * @return \ParadoxLabs\TokenBase\Helper\Data
     */
    public function getTokenbaseHelper()
    {
        return $this->tokenbaseHelper;
    }
}
