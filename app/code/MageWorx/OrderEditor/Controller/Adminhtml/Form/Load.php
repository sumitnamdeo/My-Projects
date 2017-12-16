<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OrderEditor\Controller\Adminhtml\Form;

use MageWorx\OrderEditor\Model\Order;
use MageWorx\OrderEditor\Model\Quote;
use MageWorx\OrderEditor\Model\Address;

class Load extends \Magento\Framework\App\Action\Action
{
    /**
     * Page factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * Raw factory
     *
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $rawFactory;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    
    /**
     * Order Editor helper
     *
     * @var \MageWorx\OrderEditor\Helper\Data
     */
    protected $helperData;

    /**
     * @var string
     */
    protected $blockId;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var Address
     */
    protected $address;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\RawFactory $rawFactory,
        \MageWorx\OrderEditor\Helper\Data $helperData,
        \Magento\Framework\Registry $registry,
        Order $order,
        Quote $quote,
        Address $address
    ) {
        $this->rawFactory = $rawFactory;
        $this->pageFactory = $pageFactory;
        $this->helperData = $helperData;
        $this->coreRegistry = $registry;
        $this->order = $order;
        $this->quote = $quote;
        $this->address = $address;
        return parent::__construct($context);
    }

    /**
     * Render block form
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @throws \Exception
     */
    public function execute()
    {
        try {
            $response = [
                'result' => $this->getResultHtml(),
                'status' => true
            ];
        } catch (\Exception $e) {
            $response = [
                'error' => $e->getMessage(),
                'status' => false
            ];
        }
        $result = $this->rawFactory->create()->setContents(json_encode($response));
        return $result;
    }

    /**
     * @return string
     */
    protected function getResultHtml()
    {
        $this->blockId = $this->getRequest()->getParam('block_id');

        $this->registerOrder();
        $this->registerQuote();
        $this->registerAddress();

        $resultPage = $this->pageFactory->create();
        $resultPage->addHandle('ordereditor_load_block_' . $this->blockId);
        return $resultPage->getLayout()->renderElement('content');
    }

    /**
     * Register order
     *
     * @return $this
     * @throws \Exception
     */
    private function registerOrder()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $this->order->load($orderId);
        if (!$this->order->getEntityId()) {
            throw new \Exception('Can not load order');
        }
        $this->helperData->setOrder($this->order);
    }

    /**
     * Register quote
     *
     * @return $this
     * @throws \Exception
     */
    private function registerQuote()
    {
        if ($this->blockId != 'shipping_method') {
            return;
        }

        $quoteId = $this->order->getQuoteId();
        $this->quote->load($quoteId);
        $this->helperData->setQuote($this->quote);
    }

    /**
     * Register order address
     *
     * @return $this
     * @throws \Exception
     */
    private function registerAddress()
    {
        $addressId = '';

        if ($this->blockId == 'billing_address') {
            $addressId = $this->order->getBillingAddressId();
        } elseif ($this->blockId == 'shipping_address') {
            $addressId = $this->order->getShippingAddressId();
        }

        if (!$addressId) {
            return;
        }

        $address = $this->address->loadAddress($addressId);
        if ($address->getId()) {
            $this->coreRegistry->register('order_address', $address);
        } else {
            throw new \Exception('Can not load address');
        }
    }
}
