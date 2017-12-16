<?php

namespace Emizentech\CustomSmtp\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {

        $this->_objectManager = $objectManager;
        parent::__construct($context);
    }

    public function getSmtpConfig($store_id = null)
    {
        return array(
            'auth' => strtolower($this->scopeConfig->getValue('customsmtp/emizentechcustomsmtp/auth', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id)),
            'ssl' => $this->scopeConfig->getValue('customsmtp/emizentechcustomsmtp/ssltype', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id),
            'username' => $this->scopeConfig->getValue('customsmtp/emizentechcustomsmtp/username', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id),
            'password' => $this->scopeConfig->getValue('customsmtp/emizentechcustomsmtp/password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id),
            'port' => $this->scopeConfig->getValue('customsmtp/emizentechcustomsmtp/port', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id)
         );
    }
    
    /**
     * Get system config password
     * 
     * @param \Magento\Store\Model\ScopeInterface::SCOPE_STORE $store
     */
    public function getConfigPassword($store_id = null){
        return $this->scopeConfig->getValue('customsmtp/emizentechcustomsmtp/password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id);
    }
    
    /**
     * Get system config SMTP host
     * 
     * @param \Magento\Store\Model\ScopeInterface::SCOPE_STORE $store
     */
    public function getConfigSmtpHost($store_id = null){
        return $this->scopeConfig->getValue('customsmtp/emizentechcustomsmtp/host', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id);
    }
}