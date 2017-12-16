<?php

/**
 * @author TechBandhu
 * @copyright Copyright (c) 2016 TechBandhu (http://www.TechBandhu.com)
 * @package Techbandhu_Fbpage
 */
 
namespace Techbandhu\Fbpage\Model\Source;

class Viewon implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
          [
              'label'=>'Footer',
              'value'=>'fbpage_footer'
          ],
          [
              'label'=>'Sidebar',
              'value'=>'fbpage_sidebar'
          ]
        ];
    }
}

