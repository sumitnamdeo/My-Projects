<?php

namespace Raveinfosys\Orderlog\Block\Adminhtml\Order\View\Info;

class Email extends \MageVision\UpdateOrderEmailAddress\Block\Adminhtml\Order\View\Info\Email
{
    protected $_template = 'Raveinfosys_Orderlog::order/view/info/email.phtml';
   /* protected function _prepareLayout()
    {
        $onclick = "submitAndReloadArea($('order-account-information-email'), '" . $this->getSubmitUrl() . "'); ";
        $buttonUpdate = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            ['label' => __('Update'), 'class' => 'action-save action-secondary', 'onclick' => $onclick]
        );
        $this->setChild('update_button', $buttonUpdate);
        $buttonEdit = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            ['label' => __('Edit'), 'class' => 'action-save action-secondary', 'onclick' => 'showUpdatetEmailContent()']
        );
        $this->setChild('edit_button', $buttonEdit);
        //return parent::_prepareLayout();
    }*/
}
