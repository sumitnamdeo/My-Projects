<?php
namespace Raveinfosys\Orderlog\Observer;
use Magento\Framework\Event\ObserverInterface;
class SalesOrderAfter implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		/*$admin = $objectManager->create('\Magento\Backend\Model\Auth\Session')->getUser();
		$adminUserName = $admin->getUsername();
    	$order = $observer->getEvent()->getOrder();
    	$orderSaved = $objectManager->create('\Magento\Sales\Api\Data\OrderInterface')->loadByIncrementId($order->getIncrementId());
    	if(!empty($orderSaved->getData()) && !empty($order->getData()))
    	{
	    	$Before['status'] = $orderSaved->getStatus();
	    	$Before['total_refund'] = !empty($orderSaved->getTotalRefunded()) ? $orderSaved->getTotalRefunded() : "0";
	    	$Before['customer_email'] = $orderSaved->getCustomerEmail();
	    	//$Before['shipping_description'] = $orderSaved->getShippingDescription();
	    	//$Before['shipping_method'] = $orderSaved->getShippingMethod();
	    	//$Before['shipping_amount'] = $orderSaved->getShippingAmount();
	 		$After['status'] = $order->getStatus();
	    	$After['total_refund'] = !empty($order->getTotalRefunded()) ? $order->getTotalRefunded() : "0";
	    	$After['customer_email'] = $order->getCustomerEmail();
	    	//$After['shipping_description'] = $order->getShippingDescription();
	    	//$After['shipping_method'] = $order->getShippingMethod();
			//$result = array_diff($After,$Before);
	    	 $result = array_diff($Before,$After);
			$change  = "";
			if(!empty($result)){
				$updatedAt = $order->getUpdatedAt();
				foreach ($result as $key => $value) {
					$key = str_replace('_',' ',$key);
					 $change  .= " $key is changed from $value by $adminUserName on $updatedAt.<br> ";
				}
				$order->addStatusHistoryComment($change);
			}
	    }*/
    }
}