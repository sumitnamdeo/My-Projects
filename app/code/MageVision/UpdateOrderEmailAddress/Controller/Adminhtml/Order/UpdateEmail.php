<?php
/**
 * MageVision Update Order Email Address Extension
 *
 * @category     MageVision
 * @package      MageVision_UpdateOrderEmailAddress
 * @author       MageVision Team
 * @copyright    Copyright (c) 2016 MageVision (http://www.magevision.com)
 * @license      LICENSE_MV.txt or http://www.magevision.com/license-agreement/
 */
namespace MageVision\UpdateOrderEmailAddress\Controller\Adminhtml\Order;

class UpdateEmail extends \Magento\Sales\Controller\Adminhtml\Order
{
    /**
     * Update email action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $order = $this->_initOrder();
        if ($order) {
            try {
                $email = $this->getRequest()->getPost('order_email');
                if (empty($email)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Please enter an email.')
                    );
                }
                if (!\Zend_Validate::is($email, 'EmailAddress')) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Please enter a valid email address.')
                    );
                }
                $order->setCustomerEmail($email);
                $order->save();
                return $this->resultPageFactory->create();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $response = ['error' => true, 'message' => $e->getMessage()];
            } catch (\Exception $e) {
                $response = ['error' => true, 'message' => __('Something went wrong while saving the email.')];
            }
            if (is_array($response)) {
                $resultJson = $this->resultJsonFactory->create();
                $resultJson->setData($response);
                return $resultJson;
            }
        }
        return $this->resultRedirectFactory->create()->setPath('sales/*/');
    }
}
