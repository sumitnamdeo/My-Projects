<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <support@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Block\Customer;

use Magento\Framework\View\Element\Template;

/**
 * Cards Class
 */
class Cards extends \Magento\Framework\View\Element\Template
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
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $method;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->registry = $registry;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->formKey = $formKey;

        $this->method = $this->helper->getMethodInstance($this->registry->registry('tokenbase_method'));

        parent::__construct($context, $data);
    }

    /**
     * Get stored cards for the currently-active method.
     *
     * @return array|\ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection
     */
    public function getCards()
    {
        return $this->helper->getActiveCustomerCardsByMethod($this->method->getCode());
    }

    /**
     * Get session form key.
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get the active payment method title.
     *
     * @return string
     */
    public function getPaymentMethodTitle()
    {
        return $this->method->getConfigData('title');
    }

    /**
     * Get HTML-formatted card address. This is silly, but it's how the core says to do it.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     * @see \Magento\Customer\Model\Address\AbstractAddress::format()
     */
    public function getFormattedCardAddress(\Magento\Customer\Api\Data\AddressInterface $address)
    {
        /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
        $renderer    = $this->addressConfig->getFormatByCode('html')->getRenderer();
        $addressData = $this->addressMapper->toFlatArray($address);

        return $renderer->renderArray($addressData);
    }
}
