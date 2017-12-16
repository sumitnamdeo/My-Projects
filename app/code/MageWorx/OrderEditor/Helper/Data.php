<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OrderEditor\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use MageWorx\OrderEditor\Model\Config\Source\Shipments\UpdateMode;

class Data extends AbstractHelper
{
    /**
     * XML config return to stock
     */
    const XML_PATH_RETURN_TO_STOCK = 'mageworx_order_management/order_editor/order_items/return_to_stock';

    const XML_PATH_INVOICE_UPDATE_MODE = 'mageworx_order_management/order_editor/invoice_shipment_refund/invoice_update_mode';
    const XML_PATH_SHIPMENT_UPDATE_MODE = 'mageworx_order_management/order_editor/invoice_shipment_refund/shipment_update_mode';

    const XML_PATH_SHIPPING_AUTO_RECALCULATE = 'mageworx_order_management/order_editor/shipping/auto_recalculate';

    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry
    ) {
        $this->objectManager = $objectManager;
        $this->coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * Get enable permanent order item removal
     *
     * @return int
     */
    public function getReturnToStock()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_RETURN_TO_STOCK);
    }

    /**
     * Allow keep previous invoice and add new one
     *
     * @return int
     */
    public function getIsAllowKeepPrevInvoice()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_INVOICE_UPDATE_MODE) == UpdateMode::MODE_UPDATE_ADD;
    }

    /**
     * Get update shipments mode
     * @see MageWorx\OrderEditor\Model\Config\Source\Shipments\UpdateMode
     *
     * @return string
     */
    public function getUpdateShipmentMode()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SHIPMENT_UPDATE_MODE);
    }

    /**
     * Allow shipping auto recalculation
     *
     * @return int
     */
    public function getIsAllowAutoRecalculateShipping()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SHIPPING_AUTO_RECALCULATE);
    }

    /**
     * Get current order
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('ordereditor_order');
    }

    /**
     * Set current order
     *
     * @param $order
     * @return mixed
     */
    public function setOrder($order)
    {
        $this->coreRegistry->register('ordereditor_order', $order);
    }

    /**
     * Get current order entity id
     *
     * @return mixed
     */
    public function getOrderId()
    {
        if ($this->coreRegistry->registry('current_order')) {
            $order = $this->coreRegistry->registry('current_order');
        }
        if ($this->coreRegistry->registry('order')) {
            $order = $this->coreRegistry->registry('order');
        }

        if (isset($order)) {
            $orderId = $order->getId();
        } else {
            $orderId = null;
        }
        return $orderId;
    }

    /**
     * Retrieve quote model object
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        $quote = $this->coreRegistry->registry('ordereditor_quote');
        if (!$quote) {
            $order = $this->coreRegistry->registry('ordereditor_order');
            $quote = $this->objectManager->create('Magento\Quote\Model\Quote')
                ->setStoreId($order->getStoreId())
                ->load($order->getQuoteId());
        }
        return $quote;
    }

    /**
     * Set current quote
     *
     * @param $quote
     * @return mixed
     */
    public function setQuote($quote)
    {
        $this->coreRegistry->register('ordereditor_quote', $quote);
    }

    /**
     * Retrieve customer identifier
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->coreRegistry->registry('ordereditor_order')->getCustomerId();
    }

    /**
     * Retrieve store model object
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->objectManager->create('Magento\Store\Model\Store')->load($this->getStoreId());
    }

    /**
     * Retrieve store identifier
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->coreRegistry->registry('ordereditor_order')->getStoreId();
    }

    /**
     * Round and format price
     *
     * @return float
     */
    public function roundAndFormatPrice($price)
    {
        return number_format($price, 2, '.', '');
    }
}
