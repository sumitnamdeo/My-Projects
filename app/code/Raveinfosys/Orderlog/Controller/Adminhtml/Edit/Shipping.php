<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Raveinfosys\Orderlog\Controller\Adminhtml\Edit;

use MageWorx\OrderEditor\Controller\Adminhtml\AbstractAction;
use Magento\Framework\DataObject;

class Shipping extends \MageWorx\OrderEditor\Controller\Adminhtml\Edit\Shipping
{

    /**
     * @return null|string
     * @throws \Exception
     */
   protected function updateShippingMethod()
    {
      $params = $this->prepareParams();
      $this->orderShippingChangeLog($params);
      $this->shipping->initParams($params);
      $this->shipping->updateShippingMethod();
    }
   
    public function orderShippingChangeLog($params)
    {
       /*$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
       $objDate = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface');
       $date = $objDate->date()->format('m/d/y H:i:s');
       $shippingAfter['shipping_method'] = $params['shipping_method'];
       $shippingAfter['shipping_excl_tax'] = $params['price_excl_tax'];
       $shippingAfter['shipping_incl_tax'] = $params['price_incl_tax'];
       $shippingAfter['tax_percent'] =  $params['tax_percent'];
       $shippingAfter['shipping_description'] = $params['description'];
       $orderSaved = $objectManager->create('\Magento\Sales\Api\Data\OrderInterface')->load($params['order_id']);
       $shippingBefore['shipping_method'] =  !empty($orderSaved['shipping_method']) ? $orderSaved['shipping_method'] : "null";
       $shippingBefore['shipping_excl_tax'] = !empty($orderSaved->getShippingAmount()) ? number_format((float)$orderSaved->getShippingAmount(), 2, '.', '') : "null";
       $shippingBefore['shipping_incl_tax'] = number_format((float)$orderSaved->getShippingInclTax(), 2, '.', '');
       //$shippingBefore['tax_percent'] =  number_format((float)($orderSaved->getShippingTaxAmount() * 100)/ $orderSaved->getShippingAmount(), 2, '.', '');
       $shippingBefore['tax_percent'] =  ($orderSaved->getShippingAmount() != 0) ? number_format((float)($orderSaved->getShippingTaxAmount() * 100)/ $orderSaved->getShippingAmount(), 2, '.', '') : number_format((float)$orderSaved->getShippingAmount(), 2, '.', '');
       $shippingBefore['shipping_description'] =  !empty($orderSaved->getShippingDescription()) ? $orderSaved->getShippingDescription() : "null";
      // $result = array_diff($shippingAfter,$shippingBefore);
        $result = array_diff_assoc($shippingBefore,$shippingAfter);
       // echo "<pre>";print_r($params);
        //echo "<pre>";print_r($orderSaved->getData());die('hello');
       $admin = $objectManager->create('\Magento\Backend\Model\Auth\Session')->getUser();
       $adminUserName = $admin->getUsername();
        $change  = "";
        if(!empty($result)){
            foreach ($result as $key => $value) {
                $key = str_replace('_',' ',$key);
                $change  .= " $key is changed from $value by $adminUserName on $date .<br>";
            }
            //print_r($orderSaved->getData());
            //die('hello');
          $orderSaved->addStatusHistoryComment($change);
          $orderSaved->save();
        }*/
    }
}
