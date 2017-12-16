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

namespace ParadoxLabs\Subscriptions\Block\Customer\View;

use Magento\Framework\View\Element\Template;

/**
 * Status Class
 */
class Status extends \ParadoxLabs\Subscriptions\Block\Customer\View
{
    /**
     * @var \ParadoxLabs\Subscriptions\Model\Source\Status
     */
    protected $statusModel;

    /**
     * Status constructor.
     *
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \ParadoxLabs\Subscriptions\Model\Source\Period $periodModel
     * @param \ParadoxLabs\Subscriptions\Model\Source\Status $statusModel
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
        \ParadoxLabs\Subscriptions\Model\Source\Status $statusModel,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \ParadoxLabs\Subscriptions\Helper\Vault $vaultHelper,
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

        $this->statusModel = $statusModel;
    }

    /**
     * Get status subsription.
     *
     * @return string
     */
    public function getStatusLabel()
    {
        return $this->statusModel->getOptionText(
            $this->getSubscription()->getStatus()
        );
    }
}
