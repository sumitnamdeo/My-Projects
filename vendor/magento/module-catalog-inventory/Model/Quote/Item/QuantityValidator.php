<?php
/**
 * Product inventory data validator
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogInventory\Model\Quote\Item;

use Magento\CatalogInventory\Model\Stock;

/**
 * Quantity validation.
 */
class QuantityValidator
{
    /**
     * @var QuantityValidator\Initializer\Option
     */
    protected $optionInitializer;

    /**
     * @var QuantityValidator\Initializer\StockItem
     */
    protected $stockItemInitializer;

    /**
     * Stock registry.
     *
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * Stock state.
     *
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockState;

    /**
     * @param QuantityValidator\Initializer\Option $optionInitializer
     * @param QuantityValidator\Initializer\StockItem $stockItemInitializer
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockState
     */
    public function __construct(
        QuantityValidator\Initializer\Option $optionInitializer,
        QuantityValidator\Initializer\StockItem $stockItemInitializer,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState
    ) {
        $this->optionInitializer = $optionInitializer;
        $this->stockItemInitializer = $stockItemInitializer;
        $this->stockRegistry = $stockRegistry;
        $this->stockState = $stockState;
    }

    /**
     * Check product inventory data when quote item quantity declaring
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validate(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $observer->getEvent()->getItem();
        if (!$quoteItem ||
            !$quoteItem->getProductId() ||
            !$quoteItem->getQuote() ||
            $quoteItem->getQuote()->getIsSuperMode()
        ) {
            return;
        }
        $product = $quoteItem->getProduct();
        $qty = $quoteItem->getQty();

        /** @var \Magento\CatalogInventory\Model\Stock\Item $stockItem */
        $stockItem = $this->stockRegistry->getStockItem(
            $quoteItem->getProduct()->getId(),
            $quoteItem->getProduct()->getStore()->getWebsiteId()
        );

        if (!$stockItem instanceof \Magento\CatalogInventory\Api\Data\StockItemInterface) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The stock item for Product is not valid.'));
        }

        /** @var \Magento\CatalogInventory\Api\Data\StockStatusInterface $stockStatus */
        $stockStatus = $this->stockRegistry->getStockStatus($product->getId(), $product->getStore()->getWebsiteId());

        /** @var \Magento\CatalogInventory\Api\Data\StockStatusInterface|bool $parentStockStatus */
        $parentStockStatus = false;

        /**
         * Check if product in stock. For composite products check base (parent) item stock status
         */
        if ($quoteItem->getParentItem()) {
            $product = $quoteItem->getParentItem()->getProduct();
            $parentStockStatus = $this->stockRegistry->getStockStatus(
                $product->getId(),
                $product->getStore()->getWebsiteId()
            );
        }

        if ($stockStatus) {
            if ($stockStatus->getStockStatus() == Stock::STOCK_OUT_OF_STOCK
                || $parentStockStatus && $parentStockStatus->getStockStatus() == Stock::STOCK_OUT_OF_STOCK
            ) {
                $quoteItem->addErrorInfo(
                    'cataloginventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    __('This product is out of stock.')
                );
                $quoteItem->getQuote()->addErrorInfo(
                    'stock',
                    'cataloginventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    __('Some of the products are out of stock.')
                );
                return;
            } else {
                // Delete error from item and its quote, if it was set due to item out of stock
                $this->_removeErrorsFromQuoteAndItem($quoteItem, \Magento\CatalogInventory\Helper\Data::ERROR_QTY);
            }
        }

        /**
         * Check item for options
         */
        if (($options = $quoteItem->getQtyOptions()) && $qty > 0) {
            $qty = $product->getTypeInstance()->prepareQuoteItemQty($qty, $product);
            $quoteItem->setData('qty', $qty);
            if ($stockStatus) {
                $result = $this->stockState->checkQtyIncrements(
                    $product->getId(),
                    $qty,
                    $product->getStore()->getWebsiteId()
                );
                if ($result->getHasError()) {
                    $quoteItem->addErrorInfo(
                        'cataloginventory',
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY_INCREMENTS,
                        $result->getMessage()
                    );

                    $quoteItem->getQuote()->addErrorInfo(
                        $result->getQuoteMessageIndex(),
                        'cataloginventory',
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY_INCREMENTS,
                        $result->getQuoteMessage()
                    );
                } else {
                    // Delete error from item and its quote, if it was set due to qty problems
                    $this->_removeErrorsFromQuoteAndItem(
                        $quoteItem,
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY_INCREMENTS
                    );
                }
            }

            foreach ($options as $option) {
                $result = $this->optionInitializer->initialize($option, $quoteItem, $qty);
                if ($result->getHasError()) {
                    $option->setHasError(true);

                    $quoteItem->addErrorInfo(
                        'cataloginventory',
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                        $result->getMessage()
                    );

                    $quoteItem->getQuote()->addErrorInfo(
                        $result->getQuoteMessageIndex(),
                        'cataloginventory',
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                        $result->getQuoteMessage()
                    );
                } else {
                    // Delete error from item and its quote, if it was set due to qty lack
                    $this->_removeErrorsFromQuoteAndItem(
                        $quoteItem,
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY
                    );
                }
            }
        } else {
            $result = $this->stockItemInitializer->initialize($stockItem, $quoteItem, $qty);
            if ($result->getHasError()) {
                $quoteItem->addErrorInfo(
                    'cataloginventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    $result->getMessage()
                );

                $quoteItem->getQuote()->addErrorInfo(
                    $result->getQuoteMessageIndex(),
                    'cataloginventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    $result->getQuoteMessage()
                );
            } else {
                // Delete error from item and its quote, if it was set due to qty lack
                $this->_removeErrorsFromQuoteAndItem($quoteItem, \Magento\CatalogInventory\Helper\Data::ERROR_QTY);
            }
        }
    }

    /**
     * Removes error statuses from quote and item, set by this observer
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param int $code
     * @return void
     */
    protected function _removeErrorsFromQuoteAndItem($item, $code)
    {
        if ($item->getHasError()) {
            $params = ['origin' => 'cataloginventory', 'code' => $code];
            $item->removeErrorInfosByParams($params);
        }

        $quote = $item->getQuote();
        $quoteItems = $quote->getItemsCollection();
        $canRemoveErrorFromQuote = true;

        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getItemId() == $item->getItemId()) {
                continue;
            }

            $errorInfos = $quoteItem->getErrorInfos();
            foreach ($errorInfos as $errorInfo) {
                if ($errorInfo['code'] == $code) {
                    $canRemoveErrorFromQuote = false;
                    break;
                }
            }

            if (!$canRemoveErrorFromQuote) {
                break;
            }
        }

        if ($quote->getHasError() && $canRemoveErrorFromQuote) {
            $params = ['origin' => 'cataloginventory', 'code' => $code];
            $quote->removeErrorInfosByParams(null, $params);
        }
    }
}
