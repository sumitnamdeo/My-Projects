<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OrderEditor\Model\Edit;

class Thumbnail
{
    /**
     * Order Editor helper
     *
     * @var \MageWorx\OrderEditor\Helper\Data
     */
    protected $helperData;

    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \MageWorx\OrderEditor\Helper\Data $helperData
    ) {
        $this->objectManager = $objectManager;
        $this->helperData = $helperData;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item  $item
     * @return \Magento\Catalog\Helper\Image|null
     */
    public function getImgByItem($item)
    {
        $productId = $item->getProductId();
        $product = $this->objectManager->get('Magento\Catalog\Model\Product')
            ->setStoreId($item->getStoreId())
            ->load($productId);
        switch ($product->getTypeId()) {
            case 'configurable':
                return $this->getImgByItemForConfigurableProduct($item, $product);

            default:
                if ($product->getThumbnail() && $product->getThumbnail() != 'no_selection') {
                    try {
                        return $this->objectManager->get('Magento\Catalog\Helper\Image')
                            ->init($product, 'product_listing_thumbnail');
                    } catch (\Exception $e) {
                        return;
                    }
                }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Item  $item
     * @param \Magento\Catalog\Model\Product   $product
     * @return \Magento\Catalog\Helper\Image|null
     */
    protected function getImgByItemForConfigurableProduct($item, $product)
    {
        $children = $item->getChildrenItems();
        $child = current($children);
        if ($child === false) {
            return;
        }

        $childProductId = $child->getProductId();
        if (!$childProductId) {
            return;
        }

        $childProduct = $this->objectManager->get('Magento\Catalog\Model\Product')
            ->setStoreId($item->getStoreId())
            ->load($childProductId);
        if ($childProduct->getThumbnail() && $childProduct->getThumbnail() != 'no_selection') {
            try {
                return $this->objectManager->get('Magento\Catalog\Helper\Image')
                    ->init($childProduct, 'product_listing_thumbnail');
            } catch (\Exception $e) {
                return;
            }
        } else if ($product->getThumbnail() && $product->getThumbnail() != 'no_selection') {
            try {
                return $this->objectManager->get('Magento\Catalog\Helper\Image')
                    ->init($product, 'product_listing_thumbnail');
            } catch (\Exception $e) {
                return;
            }
        }
    }
}
