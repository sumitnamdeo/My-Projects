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

namespace ParadoxLabs\TokenBase\Observer;

/**
 * CheckoutFailureEnsureCardSave Observer
 */
class CheckoutFailureEnsureCardSaveObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
    ) {
        $this->helper = $helper;
        $this->registry = $registry;
        $this->cardRepository = $cardRepository;
    }

    /**
     * If we're doing a partial refund, don't mark it as fully refunded
     * unless the full amount is done.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $card = $this->registry->registry('tokenbase_ensure_checkout_card_save');

            if ($card instanceof \ParadoxLabs\TokenBase\Model\Card && $card->getId() > 0) {
                $card->setData('no_sync', true);

                $card = $this->cardRepository->save($card);
            }
        } catch (\Exception $e) {
            // Log and ignore any errors; we don't want to throw them in this context.
            $this->helper->log(
                isset($card) && $card instanceof \ParadoxLabs\TokenBase\Model\Card ? $card->getMethod() : 'tokenbase',
                'Checkout post-failure card save failed: ' . $e->getMessage()
            );
        }
    }
}
