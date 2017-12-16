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
                  $i++;
              }
                  $orderItemBefore = array_values($orderItemBefore);
                  $orderItemAfterCount = count($orderItemAfter);
                  $orderItemBeforeCount = count($orderItemBefore);
              if(!empty($orderItemAfterCount) && !empty($orderItemBeforeCount) && $orderItemAfterCount >= $orderItemBeforeCount || $orderItemAfterCount == $orderItemBeforeCount)
               {
                foreach ($orderItemBefore as $key => $value) 
                    {
                      $result[] = array_diff_assoc($orderItemBefore[$key],$orderItemAfter[$key]);
                    }
               }
              else
                 {
                    $remorderItemBefore = $orderItemBefore;
                    foreach ($remorderItemBefore as $remkey => $remvalue) 
                    {
                      foreach ($orderItemAfter as $remafterkey => $remaftervalue) 
                      {
                        if($remvalue['item_id'] != $remaftervalue['item_id'])
                        {
                          $removedItems[] = $remvalue;
                        }else{
                          $updatedValue[] = $remvalue;
                        }
                      }
                    }
                   /*test*/
                   // print_r($orderItemBefore);
                    //print_r($removedItems);
                    //print_r($updatedValue);
                    //die('coming here');
                  }
                  if(!empty($updatedValue) && $orderItemAfterCount == count($updatedValue))
                  {
                     foreach ($updatedValue as $updatedkey => $value) 
                    {
                      $updatedResult[] = array_diff_assoc($updatedValue[$updatedkey],$orderItemAfter[$updatedkey]);
                    }
                  }
                  if(!empty($updatedResult)){
                     foreach ($updatedResult as $key => $value)
                        {
                            $currentproduct = $objectManager->create('Magento\Catalog\Model\Product')->load($updatedValue[$key]['product_id']);
                            // /$updatedResult[$key]['product_id'] = $currentproduct->getId();
                            $updatedResult[$key]['product_name'] = $currentproduct->getName();
                        }
                       // print_r($updatedResult);
                     foreach ($updatedResult as $updatedResultkey => $updatedResultvalue) 
                      {
                        foreach ($updatedResultvalue as $upkey => $upvalue) 
                          {
                            if($upkey != 'product_name' )
                            {
                              $upkey = str_replace('_',' ',$upkey);
                              $string .= "$upkey is change from $upvalue , ";
                            }
                            if($upkey == 'product_name')
                            {
                                $string .= " of product  $upvalue by $adminUserName on $date <br>";
                            }
                          }  
                      }
                  }
                  //echo $string;
                  //print_r($updatedResult);
                  //die('helo');
                    /*test*/
                 if(($orderItemBeforeCount != $orderItemAfterCount) OR  !empty($result))
                 {
                    //$string = '';
                     if(!empty($result))
                     {
                       $changedItems = array_filter($result);
                        foreach ($changedItems as $key => $value)
                        {
                            $currentproduct = $objectManager->create('Magento\Catalog\Model\Product')->load($orderItemAfter[$key]['product_id']);
                            $changedItems[$key]['product_name'] = $currentproduct->getName();
                        }
                        foreach ($changedItems as $changedItemskey => $changedItemsvalue) 
                        {
                          foreach ($changedItemsvalue as $key => $value) 
                          {
                            if($key != 'product_name')
                            {
                              $key = str_replace('_',' ',$key);
                              $string .= "$key is change from $value , ";
                            }
                            if($key == 'product_name')
                            {
                                $string .= " of product  $value by $adminUserName on $date <br>";
                            }
                          }
                        }
                      }
                  }
                  if($orderItemBeforeCount != $orderItemAfterCount && !empty($orderItemAfterCount))
                  {
                    /*test*/
                    $addedItems = $orderItemBefore;
                    print_r($orderItemAfter);
                    print_r($orderItemBefore);
                    /*foreach ($orderItemBefore as $keybefore => $valuebefore) 
                    {
                      array_splice($addedItems,$keybefore,1);
                    }*/
                    //print_r($removedItems);
                    die('helo');
                    /*test*/
                    foreach ($addedItems as $addedItemkey => $addedItemvalue) 
                    {
                      $addedProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($addedItemvalue['product_id']);
                       $string .= $addedProduct->getName().' added to order items,';
                       foreach ($addedItemvalue as $addedNewItemkey => $addedNewItemValue) 
                       {
                          if($addedNewItemkey != 'item_id' && $addedNewItemkey != 'product_id')
                          {
                             $addedNewItemkey = str_replace('_',' ',$addedNewItemkey);
                             $string .= " $addedNewItemkey is $addedNewItemValue ";
                          }
                       }
                    }
                  }
                  if($orderItemBeforeCount > $orderItemAfterCount && !empty($orderItemBeforeCount))
                  {
                    foreach ($removedItems as $removedItemkey => $removedItemvalue) 
                    {
                      $addedProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($removedItemvalue['product_id']);
                       $string .= $addedProduct->getName().' removed from order items';
                       foreach ($removedItemvalue as $removedNewItemkey => $removedNewItemValue) 
                       {
                          if($removedNewItemkey != 'item_id' && $removedNewItemkey != 'product_id')
                          {
                             $removedNewItemkey = str_replace('_',' ',$removedNewItemkey);
                             $string .= " $removedNewItemkey is $removedNewItemValue ";
                          }
                       }
                    }
                  }
                  if(!empty($updatedValue))
                  {

                  }
                  echo $string;die('hello');
                  if(!empty($string))
                  {
                    $order->addStatusHistoryComment($string);
                    $order->save();  
                  }
                     
        }   
    }
}
