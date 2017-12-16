<?php

/**
 * @author TechBandhu
 * @copyright Copyright (c) 2016 TechBandhu (http://www.TechBandhu.com)
 * @package Techbandhu_Fbpage
 */

namespace Raveinfosys\Socialshare\Block;

class Ordershare extends \Magento\Framework\View\Element\Template
{
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
			 \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
            array $data = [])
    {
		 $this->_orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context, $data);
    }

   public function getOrder() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->get('Magento\Sales\Model\Order');
        return $order_information = $order->loadByIncrementId($order_id);	
    }

}
