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

namespace ParadoxLabs\Subscriptions\Block\Adminhtml\Customer;

use Magento\Backend\Block\Template\Context;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;

/**
 * Class Tab
 */
class Tab extends TabWrapper
{
    /**
     * @var bool
     */
    protected $isAjaxLoaded = true;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * Constructor
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->authorization = $context->getAuthorization();
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        $moduleActive = $this->_scopeConfig->getValue(
            'subscriptions/general/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($moduleActive == 1 && $this->authorization->isAllowed('ParadoxLabs_Subscriptions::subscriptions')) {
            return true;
        }

        return false;
    }

    /**
     * Return Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Subscriptions');
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('subscriptions/customer/subscriptionsGrid', ['_current' => true]);
    }
}
