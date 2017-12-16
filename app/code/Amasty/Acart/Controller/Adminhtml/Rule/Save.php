<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */

namespace Amasty\Acart\Controller\Adminhtml\Rule;

use Magento\Framework\Exception\NoSuchEntityException;

class Save extends \Amasty\Acart\Controller\Adminhtml\Rule
{
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            $data = $this->getRequest()->getPostValue();

            try {
                $model = $this->_objectManager->create('Amasty\Acart\Model\Rule');

                $id = $this->getRequest()->getParam('rule_id');

                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('The wrong rule is specified.'));
                    }
                }

                $session = $this->_objectManager->get('Magento\Backend\Model\Session');

                if (isset($data['rule']) && isset($data['rule']['conditions'])) {
                    $data['conditions'] = $data['rule']['conditions'];

                    unset($data['rule']);

                    $salesRule = $this->_objectManager->create('Amasty\Acart\Model\SalesRule');
                    $salesRule->loadPost($data);

                    $data['conditions_serialized'] = serialize($salesRule->getConditions()->asArray());
                    unset($data['conditions']);
                }

                if (isset($data['store_ids']) && is_array($data['store_ids'])) {
                    $data['store_ids'] = implode(',', $data['store_ids']);
                } else {
                    $data['store_ids'] = '';
                }

                if (isset($data['customer_group_ids']) && is_array($data['customer_group_ids'])) {
                    $data['customer_group_ids'] = implode(',', $data['customer_group_ids']);
                } else {
                    $data['customer_group_ids'] = '';
                }

                $model->setData($data);

                $session->setPageData($model->getData());

                $model->save();
                $model->saveSchedule();

                $this->messageManager->addSuccess(__('You saved the rule.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('amasty_acart/*/edit', ['id' => $model->getId()]);
                    return;
                }
                $this->_redirect('amasty_acart/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('rule_id');
                if (!empty($id)) {
                    $this->_redirect('amasty_acart/*/edit', ['id' => $id]);
                } else {
                    $this->_redirect('amasty_acart/*/new');
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while saving the rule data. Please review the error log.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('amasty_acart/*/edit', ['id' => $this->getRequest()->getParam('rule_id')]);
                return;
            }
        }
        $this->_redirect('amasty_acart/*/');
    }
}
