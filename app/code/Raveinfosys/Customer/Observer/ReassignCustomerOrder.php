<?php

namespace Raveinfosys\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;

class ReassignCustomerOrder implements ObserverInterface
{

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
       $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $customer = $observer->getEvent()->getCustomer();
        $customerId = $customer->getId();
        $customerEmail = $customer->getEmail();
        $customerFirstName = $customer->getFirstname();
        $customerLastName = $customer->getLastname();
        $customerGroupId = $customer->getGroupId();

        $orders = $objectManager->create('\Magento\Sales\Model\Order')
            ->getCollection()
            ->addAttributeToFilter('customer_email', $customerEmail)
            ->addFieldToFilter('customer_is_guest', true);

        if (!empty($orders->getData())) {

            $orderIds = [];

            foreach ($orders as $orderkey => $orderdata) {
                $orderIds[] = $orderdata->getId();
            }

            foreach ($orderIds as $key => $orderId) {
                $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId);

                if($order->getId()) {
                    $order->setCustomerId($customerId);
                    $order->setCustomerFirstname($customerFirstName);
                    $order->setCustomerLastname($customerLastName);
                    $order->setCustomerGroupId($customerGroupId);
                    $order->setCustomerIsGuest(false);
                    $order->save();
                }
            }
        }
    }
}