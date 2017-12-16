<?php
namespace IWD\All\Controller\Adminhtml\Support;

use IWD\All\Model\Support;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class Send extends Action
{
    /* @var \IWD\All\Model\Support */
    protected $support;

    public function __construct(
        Context $context,
        Support $support
    ) {
        parent::__construct($context);
        $this->support = $support;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $params = $this->getRequest()->getParams();
            $this->support->sendTicket($params);
            $this->messageManager->addSuccess(__('Thank you for contacting IWD Agency\'s support team. We will review your comment and contact you shortly.'));
        } catch (\Exception $e){
            $this->messageManager->addError($e->getMessage());
        }

        return $resultRedirect->setPath('admin/system_config/edit', ['section' => 'iwd_support']);
    }
}