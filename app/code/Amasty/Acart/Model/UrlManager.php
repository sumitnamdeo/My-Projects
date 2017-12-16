<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */

namespace Amasty\Acart\Model;

use Magento\Framework\UrlInterface;

class UrlManager extends \Magento\Framework\DataObject
{
    protected $_rule;
    protected $_history;

    protected $_googleAnalyticsParams = array(
        'utm_source', 'utm_medium', 'utm_term',
        'utm_content', 'utm_campaign'
    );

    public function init(
        \Amasty\Acart\Model\Rule $rule,
        \Amasty\Acart\Model\History $history
    ){
        $this->_rule = $rule;
        $this->_history = $history;

        return $this;
    }

    public function getRule()
    {
        return $this->_rule;
    }

    protected function getParams($params = array())
    {
        $params["id"] = $this->_history->getId();
        $params["key"] = $this->_history->getPublicKey();

        foreach($this->_googleAnalyticsParams as $param){
            $val = $this->_rule->getData($param);
            if (!empty($val)){
                $params[$param] = $val;
            }
        }
        return $params;
    }

    public function get($url)
    {
        return $this->_history->getStore()->getUrl('amasty_acart/email/url', $this->getParams(array(
            'url' => urlencode(base64_encode($url)),
        )));
    }

    public function mageUrl($url)
    {
        return $this->_history->getStore()->getUrl('amasty_acart/email/url', $this->getParams(array(
                'mageUrl' => urlencode(base64_encode($url)),
        )));
    }

    public function unsubscribeUrl()
    {
        return $this->_history->getStore()->getUrl('amasty_acart/email/unsubscribe', $this->getParams());
    }
}