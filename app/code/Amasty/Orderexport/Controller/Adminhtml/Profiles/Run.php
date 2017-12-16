<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */

namespace Amasty\Orderexport\Controller\Adminhtml\Profiles;

class Run extends \Amasty\Orderexport\Controller\Adminhtml\Profiles
{
    /**
     * Items list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $result = false;
        $id     = $this->getRequest()->getParam('id');
        $download = $this->getRequest()->getParam('download', false);
        /**@var \Amasty\Orderexport\Model\Profiles $profileModel */
        $profileModel = $this->_objectManager->create('Amasty\Orderexport\Model\Profiles');
        $ids = $this->getRequest()->getParam('selected');
        if ($id && $profile = $profileModel->load($id)) {
            $this->_coreRegistry->register('amorderexport_manual_run_triggered', true, true);
            $result = $profile->setData('enabled', true)->run($ids);
        }

        if ($result) {
            if($download) {
                $this->_redirect('amasty_orderexport/history/download/',['id'=>$result]);
                return;
            }
            $this->messageManager->addSuccessMessage(__('Profile Run success'));
        } else {
            $this->messageManager->addErrorMessage(__('Profile Run failed'));
        }

        if (!is_null($ids) && $ids != 'false') {
           $this->_redirect('sales/order/index') ;
        } else {
            $this->_redirect('amasty_orderexport/*/edit', ['id' => $id]);
        }
    }
}
