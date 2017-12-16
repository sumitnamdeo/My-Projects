<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Raveinfosys\Orderlog\Controller\Adminhtml\Edit;

use MageWorx\OrderEditor\Controller\Adminhtml\AbstractAction;
use Magento\Framework\DataObject;

class Items extends \MageWorx\OrderEditor\Controller\Adminhtml\Edit\Items
{

    /**
    * @return void
    */
    protected function updateOrderItems()
    {
        $params = $this->getRequest()->getParams();
        $this->orderLog($params);
        $params = $this->getRequest()->getParams();
        $order = $this->getOrder();
        $order->editItems($params);
    }
   
    public function orderLog($params)
    {
      if(!empty($params))
      {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $admin = $objectManager->create('\Magento\Backend\Model\Auth\Session')->getUser();
        $adminUserName = $admin->getUsername();
        $objDate = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        $date = $objDate->date()->format('m/d/y H:i:s');
        $count = 0;
        $orderItemAfter = [];
          $string = '';
        foreach ($params['item'] as $key => $value) 
        {
          if($value['action'] != 'remove')
            {
              $orderItemAfter[$count]['item_id'] = $value['item_id'];
              $orderItemAfter[$count]['product_id'] = $value['product_id'];
              $orderItemAfter[$count]['price_excl_tax'] = $value['price'];
              $orderItemAfter[$count]['price_incl_tax'] = $value['price_incl_tax'];
              $orderItemAfter[$count]['qty'] = $value['fact_qty'];
              $orderItemAfter[$count]['tax_amount'] = $value['tax_amount'];
              $orderItemAfter[$count]['tax_percent'] = $value['tax_percent'];
              $orderItemAfter[$count]['discount_amount'] = $value['discount_amount'];
              $orderItemAfter[$count]['discount_percent'] = $value['discount_percent'];
              $orderItemAfter[$count]['subtotal_excl_tax'] = $value['subtotal'];
              $orderItemAfter[$count]['subtotal_incl_tax'] = $value['subtotal_incl_tax'];
              $orderItemAfter[$count]['row_total'] = $value['row_total'];
              $count++;
            }
        }
              $orderId = $params['order_id'];
              $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId);//oder data
              $orderItems = $order->getAllItems();
              $i = 1;
        foreach($orderItems as $item) 
        {
          $product = $item->getData();
          $orderItemBefore[$i]['item_id'] = $product['item_id'];
          $orderItemBefore[$i]['product_id'] = $product['product_id'];
          $orderItemBefore[$i]['price_excl_tax'] =  number_format((float)$product['price'], 2, '.', '');
          $orderItemBefore[$i]['price_incl_tax'] =  number_format((float)$product['price_incl_tax'], 2, '.', '');
          $orderItemBefore[$i]['qty'] = number_format((float)$product['qty_ordered'],0, '.', '');
          $orderItemBefore[$i]['tax_amount'] = number_format((float)$product['tax_amount'], 2, '.', '');
          $orderItemBefore[$i]['tax_percent'] = number_format((float)$product['tax_percent'], 2, '.', '');
          $orderItemBefore[$i]['discount_amount'] = number_format((float)$product['discount_amount'], 2, '.', '');
          $orderItemBefore[$i]['discount_percent'] = number_format((float)$product['discount_percent'], 2, '.', '');
          $orderItemBefore[$i]['subtotal_excl_tax'] = number_format((float)$product['row_total'], 2, '.', '');
          $orderItemBefore[$i]['subtotal_incl_tax'] = number_format((float)$product['row_total_incl_tax'], 2, '.', '');
          $orderItemBefore[$i]['row_total'] = number_format((float)(($product['row_total'] + $product['tax_amount']) - $product['discount_amount']), 2, '.', '');
          $i++;
        }
          $orderItemBefore = array_values($orderItemBefore);
          $orderItemAfterCount = count($orderItemAfter);
          $orderItemBeforeCount = count($orderItemBefore);
          /*test*/
         // print_r($orderItemAfter);
          //print_r($orderItemBefore);
          //die('hello');
          /*tets*/
          if($orderItemBeforeCount > $orderItemAfterCount)
          {
            /*getting item after remove item*/
            foreach ($orderItemBefore as $remkey => $remvalue) 
            {
                foreach ($orderItemAfter as $remafterkey => $remaftervalue) 
                {
                  if($remvalue['item_id'] == $remaftervalue['item_id'])
                  {
                    $updateCheckItems[] = $remvalue;
                  }
                }
            }
              //print_r($updateCheckItems); //need to check updated value for existing item ,items after remove,compare with $orderItemAfter
            if(!empty($updateCheckItems) && !empty($orderItemAfter))
            {
              foreach ($orderItemAfter as $changekey => $changevalue) 
              {
                $curProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($changevalue['product_id']);
                $updatedResult[] = array_diff_assoc($updateCheckItems[$changekey],$orderItemAfter[$changekey]);
                $updatedResult[$changekey]['product_id'] = $changevalue['product_id'];
                $updatedResult[$changekey]['product_name'] = $curProduct->getSku();
              }
            }
            if(!empty($updatedResult))
            {
              foreach ($updatedResult as $updatedResultkey => $updatedResultkeyvalue) 
              {
                if(count($updatedResultkeyvalue) > 2)
                {
                  foreach ($updatedResultkeyvalue as $updatedResultkeyvaluefinalkey => $updatedResultkeyvaluefinalvalue) 
                  {
                    if($updatedResultkeyvaluefinalkey != 'product_name' && $updatedResultkeyvaluefinalkey !='product_id')
                      {
                        $updatedResultkeyvaluefinalkey = str_replace('_',' ',$updatedResultkeyvaluefinalkey);
                        $string .= "$updatedResultkeyvaluefinalkey is change from $updatedResultkeyvaluefinalvalue , ";
                      }
                      if($updatedResultkeyvaluefinalkey == 'product_name')
                      {
                        $string .= " of sku  '$updatedResultkeyvaluefinalvalue' by $adminUserName on $date <br><br>";
                      }
                  }
                }
              }
            }
              /***********getting item after remove item*************/
              /*getting removed items*/
            foreach ($orderItemBefore as $orderItemBeforekey => $orderItemBeforevalue) 
            {
              $itemBeforeProductId[] = $orderItemBeforevalue['product_id'];
            }
            foreach ($orderItemAfter as $orderItemAfterkey => $orderItemAftervalue) 
            {
              $itemAfterProductId[] = $orderItemAftervalue['product_id'];
            }
            $removedProductId = array_diff($itemBeforeProductId,$itemAfterProductId);
            $removedProductId = array_values($removedProductId);
            $productCount = count($removedProductId);
            foreach ($orderItemBefore as $removekey => $removevalue) 
            {
              for ($i=0; $i < $productCount; $i++) 
              {
                if($removedProductId[$i] == $removevalue['product_id'])
                {
                  $removedProduct[$removekey]['item_id'] =  $removevalue['item_id'];
                  $removedProduct[$removekey]['product_id'] =  $removevalue['product_id'];
                  $removedProduct[$removekey]['price_excl_tax'] =  $removevalue['price_excl_tax'];
                  $removedProduct[$removekey]['price_incl_tax'] =  $removevalue['price_incl_tax'];
                  $removedProduct[$removekey]['qty'] =  $removevalue['qty'];
                  $removedProduct[$removekey]['tax_amount'] =  $removevalue['tax_amount'];
                  $removedProduct[$removekey]['tax_percent'] =  $removevalue['tax_percent'];
                  $removedProduct[$removekey]['discount_amount'] =  $removevalue['discount_amount'];
                  $removedProduct[$removekey]['discount_percent'] =  $removevalue['discount_percent'];
                  $removedProduct[$removekey]['subtotal_excl_tax'] =  $removevalue['subtotal_excl_tax'];
                  $removedProduct[$removekey]['subtotal_incl_tax'] =  $removevalue['subtotal_incl_tax'];
                }
              }
            }
            /****getting removed items*****/
            if(!empty($removedProduct))
            {
              foreach ($removedProduct as $removedItemkey => $removedItemvalue) 
              {
                $addedProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($removedItemvalue['product_id']);
                $productRemoveSku = $addedProduct->getSku();
                 $string .= "product '$productRemoveSku' removed from order items,";
                 foreach ($removedItemvalue as $removedNewItemkey => $removedNewItemValue) 
                 {
                    if($removedNewItemkey != 'item_id' && $removedNewItemkey != 'product_id')
                    {
                      $removedNewItemkey = str_replace('_',' ',$removedNewItemkey);
                      $string .= " $removedNewItemkey is $removedNewItemValue ,";
                    }
                 }
                  $string .= " by $adminUserName on $date <br><br>";
              }
            }
          }
          else if($orderItemBeforeCount < $orderItemAfterCount)
          {
            /*getting added item*/
              foreach ($orderItemBefore as $orderItemBeforekey => $orderItemBeforevalue) 
              {
                $itemBeforeProductId[] = $orderItemBeforevalue['product_id'];
              }
              foreach ($orderItemAfter as $orderItemAfterkey => $orderItemAftervalue) 
              {
                $itemAfterProductId[] = $orderItemAftervalue['product_id'];
              }
              $addedProductId = array_diff_assoc($itemAfterProductId,$itemBeforeProductId);
              $addedProductId = array_values($addedProductId);
              $productCount = count($addedProductId);
              foreach ($orderItemAfter as $addedkey => $addedvalue) 
              {
                for ($j=0; $j < $productCount; $j++) 
                {
                  if($addedProductId[$j] == $addedvalue['product_id'])
                  {
                    $addedProduct[$addedkey]['item_id'] =  $addedvalue['item_id'];
                    $addedProduct[$addedkey]['product_id'] =  $addedvalue['product_id'];
                    $addedProduct[$addedkey]['price_excl_tax'] =  $addedvalue['price_excl_tax'];
                    $addedProduct[$addedkey]['price_incl_tax'] =  $addedvalue['price_incl_tax'];
                    $addedProduct[$addedkey]['qty'] =  $addedvalue['qty'];
                    $addedProduct[$addedkey]['tax_amount'] =  $addedvalue['tax_amount'];
                    $addedProduct[$addedkey]['tax_percent'] =  $addedvalue['tax_percent'];
                    $addedProduct[$addedkey]['discount_amount'] =  $addedvalue['discount_amount'];
                    $addedProduct[$addedkey]['discount_percent'] =  $addedvalue['discount_percent'];
                    $addedProduct[$addedkey]['subtotal_excl_tax'] =  $addedvalue['subtotal_excl_tax'];
                    $addedProduct[$addedkey]['subtotal_incl_tax'] =  $addedvalue['subtotal_incl_tax'];
                  }
                }
              }
             foreach ($addedProduct as $addedItemkey => $addedItemvalue) 
              {
                $addedCurrentProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($addedItemvalue['product_id']);
                $productSku = $addedCurrentProduct->getSku();
                 $string .= "product sku '$productSku' added to order items,";
                 foreach ($addedItemvalue as $addedNewItemkey => $addedNewItemValue) 
                 {
                    if($addedNewItemkey != 'item_id' && $addedNewItemkey != 'product_id')
                    {
                       $addedNewItemkey = str_replace('_',' ',$addedNewItemkey);
                       $string .= " $addedNewItemkey is $addedNewItemValue, ";
                    }
                 }
                 $string .= " by $adminUserName on $date <br><br>";
              }
            /*getting added item*/
            /*getting existing item after added new item*/
            foreach ($orderItemAfter as $addedexistkey => $addedexistvalue) 
            {
              foreach ($orderItemBefore as $existafterkey => $existaftervalue) 
              {
                if($addedexistvalue['item_id'] == $existaftervalue['item_id'])
                {
                  $addCheckItems[] = $addedexistvalue;
                }
              }
            }
            //print_r($addCheckItems); //need to check updated value for existing item ,items after remove,compare with $orderItemAfter
            /*getting existing item after added new item*/
             if(!empty($addCheckItems) && !empty($orderItemBefore))
              {
                foreach ($addCheckItems as $addCheckkey => $addCheckkeyvalue) {
                  $curProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($addCheckkeyvalue['product_id']);
                  //$addchangeResult[] = array_diff_assoc($addCheckItems[$addCheckkey],$orderItemBefore[$addCheckkey]);
                  $addchangeResult[] = array_diff_assoc($orderItemBefore[$addCheckkey],$addCheckItems[$addCheckkey]);
                  $addchangeResult[$addCheckkey]['product_id'] = $addCheckkeyvalue['product_id'];
                  $addchangeResult[$addCheckkey]['product_name'] = $curProduct->getSku();
                }
              }
               if(!empty($addchangeResult))
             {
                foreach ($addchangeResult as $editedItemskey => $addchangeResultItemsvalue) 
                {
                  if(count($addchangeResultItemsvalue) > 2)
                  {
                    foreach ($addchangeResultItemsvalue as $addchangeResultfinalkey => $addchangeResultfinalvalue) 
                      {
                        if($addchangeResultfinalkey != 'product_name' && $addchangeResultfinalkey !='product_id')
                          {
                            $addchangeResultfinalkey = str_replace('_',' ',$addchangeResultfinalkey);
                            $string .= "$addchangeResultfinalkey is change from $addchangeResultfinalvalue , ";
                          }
                          if($addchangeResultfinalkey == 'product_name')
                          {
                            $string .= " of sku  '$addchangeResultfinalvalue' by $adminUserName on $date <br>";
                          }
                      }
                  }
                }
             }
          }
          else if($orderItemBeforeCount == $orderItemAfterCount)
          {
            foreach ($orderItemBefore as $key => $value) 
            {
              //$result[] = array_diff_assoc($orderItemAfter[$key],$orderItemBefore[$key]);
              $editedItems[] = array_diff_assoc($orderItemBefore[$key],$orderItemAfter[$key]);
              $editedItems[$key]['product_id'] = $value['product_id'];
              $currentproduct = $objectManager->create('Magento\Catalog\Model\Product')->load($value['product_id']);
              $editedItems[$key]['product_name'] = $currentproduct->getSku();
            }
           /*****updated items*****/ 
             if(!empty($editedItems))
             {
             // print_r($editedItems);die('hello');
                foreach ($editedItems as $editedItemskey => $editedItemsvalue) 
                {
                  if(count($editedItemsvalue) > 2)
                  {
                    foreach ($editedItemsvalue as $editfinalkey => $editfinalvalue) 
                      {
                        if($editfinalkey != 'product_name' && $editfinalkey !='product_id')
                          {
                            $editfinalkey = str_replace('_',' ',$editfinalkey);
                            $string .= "$editfinalkey is change from $editfinalvalue , ";
                          }
                          if($editfinalkey == 'product_name')
                          {
                            $string .= " of sku  '$editfinalvalue' by $adminUserName on $date <br>";
                          }
                      }
                  }
                }
             }
          }
        } 
        if(!empty($string)){
           $order->addStatusHistoryComment($string);
           $order->save();
        }
    }
}
