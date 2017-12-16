<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */

namespace Amasty\Acart\Controller\Email;

class Url extends \Amasty\Acart\Controller\Email
{
    protected function _getHistory()
    {
        $ret = null;

        $id = $this->getRequest()->getParam('id');
        $key = $this->getRequest()->getParam('key');

        $historyResource = $this->_objectManager->create('Amasty\Acart\Model\ResourceModel\History\Collection')
            ->addRuleQuoteData()
            ->addFieldToFilter('main_table.history_id', $id);


        if ($historyResource->getSize() > 0)
        {
            $items = $historyResource->getItems();
            $history = end($items);

            if ($history->getId() && $history->getPublicKey() == $key){
                $ret = $history;
            }
        }
        return $ret;
    }

    public function execute()
    {
        $url = $this->getRequest()->getParam('url');
        $mageUrl = $this->getRequest()->getParam('mageUrl');

        $history = $this->_getHistory();

        if ($history && ($url || $mageUrl)){

            $target = null;

            if ($url){
                $target = base64_decode($url);
            } else if ($mageUrl){
                $target = $this->_url->getUrl(base64_decode($mageUrl));
            }

            $this->_loginCustomer($history);

            $ruleQuote = $this->_objectManager->get('Amasty\Acart\Model\RuleQuote')->load($history->getRuleQuoteId());

            $ruleQuote->clickByLink($history);

            $this->getResponse()->setRedirect($target);
        } else {
            $this->_forward('defaultNoRoute');
        }

    }

    protected function _loginCustomer($history)
    {
        $customerSession = $this->_objectManager->get('Magento\Customer\Model\Session');
        $checkoutSession = $this->_objectManager->get('Magento\Checkout\Model\Session');

        if ($customerSession->isLoggedIn()){
            if ($history->getCustomerId() != $customerSession->getCustomerId()){
                $customerSession->logout();
            }
        }

        // customer. login
        if ($history->getCustomerId()){

            $customer = $this->_objectManager->get('Magento\Customer\Model\Customer')->load($history->getCustomerId());

            if ($customer->getId()) {
                $customerSession->setCustomerAsLoggedIn($customer);
            }
        }
        elseif ($history->getQuoteId()){
            //visitor. restore quote in the session
            $quote = $this->_objectManager->get('Magento\Quote\Model\Quote')->load($history->getQuoteId());

            if ($quote){
                $checkoutSession->replaceQuote($quote);
                $quote->getBillingAddress()->setEmail($history->getEmail());
            }
        }

        if ($history->getSalesRuleCoupon()){

            $code = $history->getSalesRuleCoupon();
            $quote = $checkoutSession->getQuote();
            if ($code && $quote){

                $quote->setCouponCode($code)
                    ->collectTotals()
                    ->save();
            }
        }

    }
}