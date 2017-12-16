<?php

namespace IWD\All\Block\Adminhtml;

class Support extends \Magento\Backend\Block\Template
{
    protected $_urlBuilder;
    protected $_authSession;
    protected $_moduleList;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->_authSession = $authSession;
        $this->_moduleList = $moduleList;

        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        $section = $this->getRequest()->getParam('section', false);
        if ($section == 'iwd_support') {
            return parent::_toHtml();
        } else {
            return '';
        }
    }

    public function getFormUrl()
    {
        return $this->_urlBuilder->getUrl('iwdall/support/send');
    }

    public function getIwdExtensions()
    {
        $modules = $this->_moduleList->getNames();

        $dispatchResult = new \Magento\Framework\DataObject($modules);
        $this->_eventManager->dispatch(
            'adminhtml_system_config_advanced_disableoutput_render_before',
            ['modules' => $dispatchResult]
        );
        $modules = $dispatchResult->toArray();

        sort($modules);

        $options = '';
        foreach ($modules as $moduleName) {
            if(strpos(strtolower($moduleName), 'iwd') === 0){
                $options .= '<option value="'.$moduleName.'">'.$moduleName.'</option>';
            }
        }

        return $options;
    }

    public function getAdminEmail()
    {
        return $this->_authSession->getUser()->getEmail();
    }

    public function getAdminName()
    {
        return $this->_authSession->getUser()->getUsername();
    }
}