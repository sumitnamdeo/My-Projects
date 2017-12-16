<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */

namespace Amasty\Acart\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class History extends \Magento\Framework\Model\AbstractModel
{
    protected $_dateTime;
    protected $_date;
    protected $_storeManager;
    protected $_store;

    protected $_groupRepository;
    protected $_searchCriteriaBuilder;
    protected $_transportBuilder;
    protected $_templateFactory;
    protected $_message;
    protected $_scopeConfig;

    const STATUS_PROCESSING = 'processing';
    const STATUS_SENT = 'sent';
    const STATUS_BLACKLIST = 'blacklist';
    const STATUS_ADMIN = 'admin';


    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Mail\TransportInterfaceFactory $mailTransportFactory,
        \Magento\Framework\Mail\Template\FactoryInterface $templateFactory,
        \Magento\Framework\Mail\MessageFactory $messageFactory,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,

        array $data = []
    ){

        $this->_dateTime = $dateTime;
        $this->_date = $date;

        $this->_storeManager = $storeManager;
        $this->_groupRepository = $groupRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;

        $this->_templateFactory = $templateFactory;
        $this->_messageFactory = $messageFactory;

        $this->_mailTransportFactory = $mailTransportFactory;

        $this->_scopeConfig = $scopeConfig;

        parent::__construct($context, $registry, $resource, $resourceCollection);
    }

    public function _construct()
    {
        $this->_init('Amasty\Acart\Model\ResourceModel\History');
    }

    protected function _getCouponToDate($days, $deliveryTime){
        return $this->_dateTime->formatDate(
            $this->_date->gmtTimestamp()
            + $days * 24 * 3600
            + $deliveryTime
        );
    }

    public function create(
        \Amasty\Acart\Model\RuleQuote $ruleQuote,
        \Amasty\Acart\Model\Schedule $schedule,
        \Amasty\Acart\Model\Rule $rule,
        \Magento\Quote\Model\Quote $quote,
        $time)
    {
        $couponData = array();

        if ($schedule->getUseShoppingCartRule())
        {

            $salesRule = \Magento\Framework\App\ObjectManager::getInstance()
                                    ->create('Magento\SalesRule\Model\Rule')->load($schedule->getSalesRuleId());

            $salesCoupon = $this->_generateCouponPool($salesRule);

            $couponData['sales_rule_id'] = $salesRule->getId();
            $couponData['sales_rule_coupon_id'] = $salesCoupon->getId();
            $couponData['sales_rule_coupon'] = $salesCoupon->getCode();
        }
        else if ($schedule->getSimpleAction())
        {
            $salesRule = $this->_createCoupon($ruleQuote, $schedule, $rule);

            $couponData['sales_rule_id'] = $salesRule->getId();
            $couponData['sales_rule_coupon'] = $salesRule->getCouponCode();
        }

        $this->setData(array_merge(array(
            'rule_quote_id' => $ruleQuote->getId(),
            'schedule_id' => $schedule->getId(),
            'status' => self::STATUS_PROCESSING,
            'public_key' => uniqid(),

            'scheduled_at' => $this->_dateTime->formatDate($time + $schedule->getDeliveryTime()),
        ), $couponData));

        $this->save();

        $template = $this->_createEmailTemplate($ruleQuote, $schedule, $rule, clone $quote);

        $this->addData([
            'email_body' => $template->processTemplate(),
            'email_subject' => $template->getSubject(),
        ]);

        $this->save();

        return $this;
    }

    protected function _generateCouponPool(\Magento\SalesRule\Model\Rule $rule)
    {
        $salesCoupon = null;

        $generator = $rule->getCouponCodeGenerator();

        $generator = \Magento\Framework\App\ObjectManager::getInstance()
                    ->create('Magento\SalesRule\Model\Coupon\Massgenerator');

        $generator->setData(array(
            'rule_id' => $rule->getId(),
            'qty' => 1,
            'length' => 12,
            'format' => 'alphanum',
            'prefix' => '',
            'suffix' => '',
            'dash' => '0',
            'uses_per_coupon' => '0',
            'uses_per_customer' => '0',
            'to_date' => '',
        ));


       
        $generator->generatePool();
        $generated = $generator->getGeneratedCount();

        $resourceCoupon = \Magento\Framework\App\ObjectManager::getInstance()
                                            ->create('Magento\SalesRule\Model\ResourceModel\Coupon\Collection');

        $resourceCoupon
            ->addFieldToFilter('main_table.rule_id', $rule->getId())
            ->getSelect()
            ->joinLeft(
                array('h' => $resourceCoupon->getTable('amasty_acart_history')),
                'main_table.coupon_id = h.sales_rule_coupon_id',
                array()
            )->where('h.history_id is null')
            ->order('main_table.coupon_id desc')
            ->limit(1);

        $items = $resourceCoupon->getItems();

        if (count($items) > 0){
            $salesCoupon = end($items);
        }

        return $salesCoupon;
    }

    protected function _createEmailTemplate(
        \Amasty\Acart\Model\RuleQuote $ruleQuote,
        \Amasty\Acart\Model\Schedule $schedule,
        \Amasty\Acart\Model\Rule $rule,
        \Magento\Quote\Model\Quote $quote
    ){
        $vars = [
            'quote' => $quote,
            'rule' => $rule,
            'ruleQuote' => $ruleQuote,
            'history' => $this,
            'urlmanager' => \Magento\Framework\App\ObjectManager::getInstance()
                            ->create('Amasty\Acart\Model\UrlManager')->init($rule, $this),
            'formatmanager' => \Magento\Framework\App\ObjectManager::getInstance()
                            ->create('Amasty\Acart\Model\FormatManager')->init([
                                \Amasty\Acart\Model\FormatManager::TYPE_HISTORY => $this,
                                \Amasty\Acart\Model\FormatManager::TYPE_QUOTE => $quote,
                                \Amasty\Acart\Model\FormatManager::TYPE_RULE_QUOTE => $ruleQuote
                            ]),
        ];

        if ($this->getSalesRuleCoupon()){
            $quote->setCouponCode($this->getSalesRuleCoupon())->collectTotals();
        }

        $template = $this->_templateFactory->get($schedule->getTemplateId())
            ->setVars($vars)
            ->setOptions([
                'area' => Area::AREA_FRONTEND,
                'store' => $ruleQuote->getStoreId()
        ]);

        if ($this->getSalesRuleCoupon()) {
            $quote->setCouponCode("")->collectTotals();
        }

        return $template;
    }

    protected function _initDiscountPrices(\Magento\Quote\Model\Quote $quote)
    {
        $this->setSubtotal($quote->getSubtotal());
        $this->setGrandTotal($quote->getGrandTotal());
    }

    public function getStore($storeId = null)
    {
        if (!$storeId){
            $storeId = $this->getStoreId();
        }

        if (!$this->_store) {
            $this->_store = $this->_storeManager->getStore($storeId);
        }

        return $this->_store;
    }

    protected function _createCoupon(
        \Amasty\Acart\Model\RuleQuote $ruleQuote,
        \Amasty\Acart\Model\Schedule $schedule,
        \Amasty\Acart\Model\Rule $rule
    ){

        $store = $this->getStore($ruleQuote->getStoreId());

        $salesRule = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Magento\SalesRule\Model\Rule');

        $salesRule->setData(array(
            'name' => 'Amasty: Abandoned Cart Coupon #' . $ruleQuote->getCustomerEmail(),
            'is_active' => '1',
            'website_ids' => array(0 => $store->getWebsiteId()),
            'customer_group_ids' => $this->_getGroupsIds($rule),
            'coupon_code' => strtoupper(uniqid()),
            'uses_per_coupon' => 1,
            'coupon_type' => 2,
            'from_date' => '',
            'to_date' => $this->_getCouponToDate($schedule->getExpiredInDays(), $schedule->getDeliveryTime()),
            'uses_per_customer' => 1,
            'simple_action' => $schedule->getSimpleAction(),
            'discount_amount' => $schedule->getDiscountAmount(),
            'stop_rules_processing' => '0',
            'from_date' => '',
        ));

        if ($schedule->getDiscountQty() > 0){
            $salesRule->setDiscountQty($schedule->getDiscountQty());
        }

        if ($schedule->getDiscountStep() > 0){
            $salesRule->setDiscountStep($schedule->getDiscountStep());
        }

        $salesRule->setConditionsSerialized(serialize($this->_getConditions($rule)));

        $salesRule->save();

        return $salesRule;
    }

    protected function _getGroupsIds(\Amasty\Acart\Model\Rule $rule)
    {
        $groupsIds = [];
        $strGroupIds = $rule->getCustomerGroupIds();

        if (!empty($strGroupIds)){
            $groupsIds = explode(',', $strGroupIds);
        } else {
            foreach($this->_groupRepository->getList($this->_searchCriteriaBuilder->create())
                                       ->getItems() as $group){
                $groupsIds[] = $group->getId();
            }
        }

        return $groupsIds;

    }

    protected function _getConditions(\Amasty\Acart\Model\Rule $rule)
    {
        $salesRuleConditions = [];
        $conditions = $rule->getSalesRule()->getConditions()->asArray();

        if (isset($conditions['conditions'])){
            foreach($conditions['conditions'] as $idx => $condition)
            {
                if ($condition['attribute'] !== \Amasty\Acart\Model\SalesRule\Condition\Carts::ATTRIBUTE_CARDS_NUM){

                    $salesRuleConditions[] = $condition;
                }
            }
        }

        return array(
            'type'       => 'Magento\SalesRule\Model\Rule\Condition\Combine',
            'attribute' => '',
            'operator' => '',
            'value'      => '1',
            'is_value_processed' => '',
            'aggregator' => 'all',
            'conditions' => $salesRuleConditions
        );
    }

    public function execute($testMode = false)
    {
        $this->setExecutedAt($this->_dateTime->formatDate($this->_date->gmtTimestamp()))
            ->save();


        $blacklist = \Magento\Framework\App\ObjectManager::getInstance()
                    ->create('Amasty\Acart\Model\Blacklist')->load($this->getCustomerEmail(),'customer_email');

        if (!$blacklist->getId() || $testMode){
            $this->_sendEmail($testMode);
            $this->setStatus(self::STATUS_SENT);
        } else {
            $this->setStatus(self::STATUS_BLACKLIST);
        }

        $this->setFinishedAt($this->_dateTime->formatDate($this->_date->gmtTimestamp()))
           ->save();
    }

    protected function _sendEmail($testMode = false)
    {
        $senderName = $this->_scopeConfig->getValue('amasty_acart/email_templates/sender_name', ScopeInterface::SCOPE_STORE, $this->getStoreId());

        $senderEmail = $this->_scopeConfig->getValue('amasty_acart/email_templates/sender_email', ScopeInterface::SCOPE_STORE, $this->getStoreId());

        $bcc = $this->_scopeConfig->getValue('amasty_acart/email_templates/bcc', ScopeInterface::SCOPE_STORE, $this->getStoreId());

        $safeMode = $this->_scopeConfig->getValue('amasty_acart/testing/safe_mode', ScopeInterface::SCOPE_STORE, $this->getStoreId());

        $recipientEmail = $this->_scopeConfig->getValue('amasty_acart/testing/recipient_email', ScopeInterface::SCOPE_STORE, $this->getStoreId());

        $name = array(
            $this->getCustomerFirstname(),
            $this->getCustomerLastname(),
        );

        $to = $this->getCustomerEmail();

        if ($testMode || $safeMode){
            $to = $recipientEmail;
        }

        $message = $this->_messageFactory->create();

        $message
            ->addTo($to, implode(' ', $name))
            ->setFrom($senderEmail, $senderName)
            ->setMessageType(\Magento\Framework\Mail\MessageInterface::TYPE_HTML)
            ->setBody($this->getEmailBody())
            ->setSubject($this->getEmailSubject());

        if (!empty($bcc) && !$testMode && !$safeMode){
            $message->addBcc(explode(',', $bcc));
        }

        $mailTransport = $this->_mailTransportFactory->create(['message' => clone $message]);

        $mailTransport->sendMessage();
    }
}