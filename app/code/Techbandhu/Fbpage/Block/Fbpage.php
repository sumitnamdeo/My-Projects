<?php

/**
 * @author TechBandhu
 * @copyright Copyright (c) 2016 TechBandhu (http://www.TechBandhu.com)
 * @package Techbandhu_Fbpage
 */

namespace Techbandhu\Fbpage\Block;

class Fbpage extends \Magento\Framework\View\Element\Template
{
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function getIsActive()
    {
        $viewOn = explode(',', $this->getVarOption('viewon'));
        return (boolean)$this->_storeManager->getStore()->getConfig('fbpage/general/active') &&
        in_array($this->getNameInLayout(), $viewOn);
    }

    public function getPageUrl()
    {
        return urlencode($this->_storeManager->getStore()->getConfig('fbpage/general/page_url'));
    }
    
    public function getVarOption($option)
    {
        return $this->_storeManager->getStore()->getConfig('fbpage/general/'.$option);
    }

    public function getBoolOption($option)
    {
        if($this->_storeManager->getStore()->getConfig('fbpage/general/'.$option)){
            return "true";
        }else{
            return "false";
        }
    }

    public function getWidget()
    {
        $url ="https://www.facebook.com/plugins/page.php?href=".$this->getPageUrl()."&tabs=".$this->getVarOption('tabs')."&width=".$this->getVarOption('box_width')."&height=".$this->getVarOption('box_height')."&small_header=".$this->getBoolOption('is_small_header')."&adapt_container_width=".$this->getBoolOption('adapt_width')."&hide_cover=".$this->getBoolOption('hide_cover_photo')."&show_facepile=".$this->getBoolOption('show_face')."&appId";

        return $url;
    }
}
