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

/**
 * Form Class
 */
class Form extends \Magento\Customer\Block\Address\Edit
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Card
     */
    protected $card;

    /**
     * @var \ParadoxLabs\TokenBase\Model\CardFactory
     */
    protected $cardFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \ParadoxLabs\TokenBase\Api\MethodInterface
     */
    protected $method;

    /**
     * @var \Magento\Payment\Block\Form\Cc
     */
    protected $ccBlock;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Method\Factory
     */
    protected $tokenbaseMethodFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession *Proxy
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer *Proxy
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param \ParadoxLabs\TokenBase\Model\Method\Factory $tokenbaseMethodFactory
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Model\Method\Factory $tokenbaseMethodFactory,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->cardFactory = $cardFactory;
        $this->registry = $registry;
        $this->tokenbaseMethodFactory = $tokenbaseMethodFactory;

        $this->method = $this->tokenbaseMethodFactory->getMethodInstance($this->getCode());

        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $customerSession,
            $addressRepository,
            $addressDataFactory,
            $currentCustomer,
            $dataObjectHelper,
            $data
        );
    }

    /**
     * Get the active payment method code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->registry->registry('tokenbase_method');
    }

    /**
     * Get the active payment method.
     *
     * @return \ParadoxLabs\TokenBase\Api\MethodInterface
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the TokenBase helper.
     *
     * @return \ParadoxLabs\TokenBase\Helper\Data
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Return active card model (or an empty card)
     *
     * @return \ParadoxLabs\TokenBase\Model\Card
     */
    public function getCard()
    {
        if ($this->card === null) {
            try {
                $this->card = $this->helper->getActiveCard($this->getCode());
            } catch (\Exception $e) {
                $this->card = $this->cardFactory->create();
            }
        }

        return $this->card;
    }

    /**
     * Return the associated address.
     *
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function getAddress()
    {
        return $this->getCard()->getAddressObject();
    }

    /**
     * Return the specified numbered street line.
     *
     * @param int $lineNumber
     * @return string
     */
    public function getStreetLine($lineNumber)
    {
        $street = $this->getAddress()->getStreet();

        return isset($street[$lineNumber - 1]) ? $street[$lineNumber - 1] : '';
    }

    /**
     * Generate name block html.
     *
     * @return string
     */
    public function getNameBlockHtml()
    {
        /** @var \Magento\Customer\Block\Widget\Name $nameBlock */
        $nameBlock = $this->getLayout()
                          ->createBlock('Magento\Customer\Block\Widget\Name');

        $nameBlock->setObject($this->getAddress());
        $nameBlock->setData('field_name_format', 'billing[%s]');

        return $nameBlock->toHtml();
    }

    /**
     * Return the form submit action.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->getUrl('*/*/save', ['_secure' => true]);
    }

    /**
     * Return the Url to go back.
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/index', ['_secure' => true, 'method' => $this->getCode()]);
    }

    /**
     * Return whether or not this is a card edit.
     *
     * @return bool
     */
    public function isEdit()
    {
        return $this->getCard()->getId() > 0;
    }

    /**
     * @return \Magento\Payment\Block\Form\Cc
     */
    public function getCcBlock()
    {
        if ($this->ccBlock === null) {
            $this->ccBlock = $this->getLayout()->createBlock('Magento\Payment\Block\Form\Cc');
            $this->ccBlock->setMethod($this->helper->getMethodInstance($this->getCode()));
        }

        return $this->ccBlock;
    }

    /**
     * Retrieve the Customer Data using the customer Id from the customer session.
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer()
    {
        return $this->helper->getCurrentCustomer();
    }
}
