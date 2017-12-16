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
 * Main tab
 */
class Main extends Generic implements TabInterface
{
    /**
     * @var \ParadoxLabs\Subscriptions\Model\Source\Status
     */
    protected $statusModel;

    /**
     * @var \ParadoxLabs\Subscriptions\Model\Source\Period
     */
    protected $periodModel;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Main constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \ParadoxLabs\Subscriptions\Model\Source\Status $statusModel
     * @param \ParadoxLabs\Subscriptions\Model\Source\Period $periodModel
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \ParadoxLabs\Subscriptions\Model\Source\Status $statusModel,
        \ParadoxLabs\Subscriptions\Model\Source\Period $periodModel,
        array $data
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->statusModel = $statusModel;
        $this->periodModel = $periodModel;
        $this->customerRepository = $customerRepository;
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
        return __('Details');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Details');
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

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('subscription_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Subscription Details')]);

        if ($subscription->getId()) {
            $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $subscription->getQuote();
        $products = '';

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            $products .= sprintf('%s (SKU: %s)<br />', $item->getName(), $item->getSku());
        }

        $fieldset->addField(
            'product_label',
            'note',
            [
                'name'  => 'product',
                'label' => __('Product'),
                'text'  => $products,
            ]
        );

        $fieldset->addField(
            'description',
            'text',
            [
                'name'  => 'description',
                'label' => __('Description'),
                'title' => __('Description'),
            ]
        );

        $fieldset->addField(
            'status_label',
            'note',
            [
                'name'  => 'status',
                'label' => __('Status'),
                'text'  => $this->statusModel->getOptionText($subscription->getStatus()),
            ]
        );

        $fieldset->addField(
            'created_at_formatted',
            'note',
            [
                'name'  => 'created_at',
                'label' => __('Started'),
                'text'  => $this->_localeDate->formatDateTime(
                    $subscription->getCreatedAt(),
                    \IntlDateFormatter::MEDIUM
                )
            ]
        );

        $fieldset->addField(
            'last_run_formatted',
            'note',
            [
                'name'  => 'last_run',
                'label' => __('Last run'),
                'text'  => $this->_localeDate->formatDateTime(
                    $subscription->getLastRun(),
                    \IntlDateFormatter::MEDIUM
                )
            ]
        );

        $subscription->setData(
            'next_run_formatted',
            $this->_localeDate->date($subscription->getNextRun())
        );
        $fieldset->addField(
            'next_run_formatted',
            'date',
            [
                'name'        => 'next_run',
                'label'       => __('Next run'),
                'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::MEDIUM),
                'time_format' => $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT),
                'class'       => 'validate-date validate-date-range date-range-custom_theme-from',
                'style'       => 'width:200px',
            ]
        );

        $fieldset->addField(
            'run_count',
            'label',
            [
                'name'  => 'run_count',
                'label' => __('Number of times billed'),
            ]
        );

        $fieldset->addField(
            'frequency_count',
            'text',
            [
                'name'  => 'frequency_count',
                'label' => __('Frequency: Every'),
                'title' => __('Frequency: Every'),
            ]
        );

        $fieldset->addField(
            'frequency_unit',
            'select',
            [
                'name'    => 'frequency_unit',
                'title'   => __('Frequency Unit'),
                'options' => $this->periodModel->getOptionArrayPlural(),
            ]
        );

        $fieldset->addField(
            'length',
            'text',
            [
                'name'  => 'length',
                'label' => __('Length'),
                'title' => __('Length'),
                'note'  => __('Number of cycles the subscription should run. 0 for indefinite.'),
            ]
        );

        if ($subscription->getCustomerId() > 0) {
            try {
                $customer = $this->customerRepository->getById($subscription->getCustomerId());

                $fieldset->addField(
                    'customer',
                    'note',
                    [
                        'name'  => 'customer',
                        'label' => __('Customer'),
                        'text'  => __(
                            '<a href="%1">%2 %3</a> (%4)',
                            $this->escapeUrl(
                                $this->getUrl('customer/index/edit', ['id' => $subscription->getCustomerId()])
                            ),
                            $customer->getFirstname(),
                            $customer->getLastname(),
                            $customer->getEmail()
                        )
                    ]
                );
            } catch (\Exception $e) {
                // Do nothing on exception.
            }
        }

        // TODO: Add notes field?
        // TODO: Consider adding store, subtotal

        $form->setValues($subscription->getData());
        $this->setForm($form);

        $this->_eventManager->dispatch('adminhtml_subscription_view_tab_main_prepare_form', ['form' => $form]);

        parent::_prepareForm();

        return $this;
    }
}
