<?php
/**
 * Customer method model
 *
 * @category    Raveinfosys
 * @package     Raveinfosys_Socialshare
 * @author      Raveinfosys Inc.
 */
namespace Raveinfosys\Socialshare\Block\Onepage;

/**
 * One page checkout success page
 */
class Share extends \Magento\Checkout\Block\Onepage\Success
{
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $imageBuilder;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = [],
        \Magento\Catalog\Block\Product\Context $product
    ) {
        $this->imageBuilder = $product->getImageBuilder();
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
    }


    /**
     * Retrieve product image
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     * @param array $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }

    public function getOrder()
    {
        return $this->_checkoutSession->getLastRealOrder();
    }

    /**
     * Check Product has URL
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function hasProductUrl($product)
    {
        if ($product->getVisibleInSiteVisibilities()) {
            return true;
        }
        if ($product->hasUrlDataObject()) {
            if (in_array($product->hasUrlDataObject()->getVisibility(), $product->getVisibleInSiteVisibilities())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve Product URL using UrlDataObject
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $additional the route params
     * @return string
     */
    public function getProductUrl($product, $additional = [])
    {
        if ($this->hasProductUrl($product)) {
            if (!isset($additional['_escape'])) {
                $additional['_escape'] = true;
            }
            return $product->getUrlModel()->getUrl($product, $additional);
        }

        return '#';
    }

    public function getFacebookUrl($_product)
    {
        return "http://facebook.com/sharer.php?u=" .  rawurlencode($this->getProductUrl($_product)) . '&t=' .  rawurlencode($_product->getName());
    }
}
