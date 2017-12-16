<?php
namespace Raveinfosys\Orderlog\Observer;
use Magento\Framework\Event\ObserverInterface;
class salesOrderAddressAfter implements ObserverInterface
{
    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $addressAfter = $observer->getEvent()->getData('address');
        //echo "<pre>";print_r($addressAfter->getData());
        //echo $addressAfter->getVatId();
        //die;
        if(!empty($addressAfter->getData()))
        {
            $After['prefix'] = (!empty($addressAfter->getPrefix()) ? $addressAfter->getPrefix() : 'null');
            $After['first_name'] = $addressAfter->getFirstName();
            $After['middle_name'] = (!empty($addressAfter->getMiddleName()) ? $addressAfter->getMiddleName() : 'null');
            $After['last_name'] = $addressAfter->getLastName();
            $After['suffix'] = (!empty($addressAfter->getSuffix()) ? $addressAfter->getSuffix() : 'null');
            $After['company'] = (!empty($addressAfter->getCompany()) ? $addressAfter->getCompany() : 'null');
            $After['post_code'] = $addressAfter->getPostCode();
            $After['street'] = $addressAfter->getData()['street'];
            $After['city'] = $addressAfter->getCity();
            $After['email'] = $addressAfter->getEmail();
            $After['telephone'] = $addressAfter->getTelephone();
            $After['fax'] = !empty($addressAfter->getFax()) ? $addressAfter->getFax() : "null";
            $After['vat_number'] = (!empty($addressAfter->getVatId()) ? $addressAfter->getVatId() : 'null');
            $region = $objectManager->create('Magento\Directory\Model\Region')
                            ->load($addressAfter->getRegionId());
            $country = $objectManager->create('\Magento\Directory\Model\Country')
                            ->load($addressAfter->getCountryId());                
            $After['region'] = (!empty($addressAfter->getRegion()) ? $addressAfter->getRegion() : $region->getName());
            $After['country'] = $country->getName();
            $order = $objectManager->create('\Magento\Sales\Model\Order')->load($addressAfter->getParentId());
            $objDate = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface');
            $date = $objDate->date()->format('m/d/y H:i:s');
            $addressArr = ($addressAfter->getAddressType() == 'billing') ? $order->getBillingAddress() : $order->getShippingAddress();
            $admin = $objectManager->create('\Magento\Backend\Model\Auth\Session')->getUser();
            $adminUserName = $admin->getUsername();
            $Before['prefix'] = !empty($addressArr->getPrefix()) ? $addressArr->getPrefix() : "null";
            $Before['first_name'] = $addressArr->getFirstName();
            $Before['middle_name'] = !empty($addressArr->getMiddleName()) ? $addressArr->getMiddleName() : "null";
            $Before['last_name'] = $addressArr->getLastName();
            $Before['suffix'] = !empty($addressArr->getSuffix()) ? $addressArr->getSuffix() : "null";
            $Before['company'] = !empty($addressArr->getCompany()) ? $addressArr->getCompany() : "null";
            $Before['post_code'] = $addressArr->getPostCode();
            $Before['street'] = $addressArr->getData()['street'];
            $Before['city'] = $addressArr->getCity();
            $Before['email'] = $addressArr->getEmail();
            $Before['telephone'] = $addressArr->getTelephone();
            $Before['fax'] = !empty($addressArr->getFax()) ? $addressArr->getFax() : "null";
            $Before['vat_number'] = !empty($addressArr->getVatId()) ? $addressArr->getVatId() : 'null';
            $addressType = $addressArr->getAddressType();
            $region = $objectManager->create('Magento\Directory\Model\Region')
                            ->load($addressArr->getRegionId());
            $country = $objectManager->create('\Magento\Directory\Model\Country')
                            ->load($addressArr->getCountryId());                   
            $Before['region'] = (!empty($addressArr->getRegion()) ? $addressArr->getRegion() : $region->getName());
            $Before['country'] = $country->getName();
    }
        $result = array_diff_assoc($Before,$After);
        //$result = array_diff($After,$Before);
        $change  = "";
        if(!empty($result)){
            $updatedAt = $order->getUpdatedAt();
            foreach ($result as $key => $value) {
                $key = str_replace('_',' ',$key);
                $change  .= "$addressType $key is changed from $value by $adminUserName on $date .<br>";
            }
            $order->addStatusHistoryComment($change);
            $order->save();
        }
    }
}
