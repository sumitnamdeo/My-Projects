<?php
/**
 * @author TechBandhu
 * @copyright Copyright (c) 2016 TechBandhu (http://www.TechBandhu.com)
 * @package Techbandhu_Fbpage
 */

namespace Techbandhu\Fbpage\Model\Source;

class Fbtabs implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
          [
              'label'=>'timeline',
              'value'=>'timeline'
          ],
          [
              'label'=>'events',
              'value'=>'events'
          ],
          [
              'label'=>'messages',
              'value'=>'messages'
          ]
        ];
    }
}

