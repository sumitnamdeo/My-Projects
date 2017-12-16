<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */


namespace Amasty\Acart\Block\Adminhtml\Rule\Edit\Tab\Schedule;

use Magento\Backend\Block\Widget;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Amasty\Acart\Controller\RegistryConstants;

class Content extends Widget implements RendererInterface
{
    const MAX_SALES_RULES = 100;

    protected $_template = 'rule/schedule.phtml';
    protected $_salesRuleCollection;
    protected $_emailTemplateCollection;
    protected $_coreRegistry;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templatesFactory,
        \Magento\Framework\Registry $registry,
        \Magento\SalesRule\Model\Rule $rule,
        array $data = []
    ) {

        $this->_coreRegistry = $registry;

        $this->_emailTemplateCollection = $templatesFactory->create()
            ->addFilter('orig_template_code', 'amasty_acart_template');

        $this->_salesRuleCollection = $rule->getCollection()
            ->addFilter('use_auto_generation', 1);


        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            ['label' => __('Add Record'), 'onclick' => 'return amastyAcartSchedule.addItem();', 'class' => 'add']
        );

        $button->setName('add_record_button');

        $this->setChild('add_record_button', $button);


        return parent::_prepareLayout();
    }

    public function getAddRecordButtonHtml()
    {
        return $this->getChildHtml('add_record_button');
    }


    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }

    public function getNumberOptions($number)
    {
        $ret = array('<option value="">-</option>');
        for($index = 1; $index <= $number; $index++){
            $ret[] = '<option value="' . $index . '" >' . $index . '</option>';
        }
        return implode('', $ret);
    }

    public function getEmailTemplateCollection()
    {
        return $this->_emailTemplateCollection;
    }

    public function getSalesRuleCollection()
    {
        return $this->_salesRuleCollection;
    }

    public function isShowSalesRuleSelect()
    {
        return $this->getSalesRuleCollection()->getSize() < self::MAX_SALES_RULES;
    }

    protected function _getRule()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_AMASTY_ACART_RULE);
    }

    public function getScheduleCollection()
    {
        return $this->_getRule()->getScheduleCollection();
    }

}