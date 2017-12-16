<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Amasty\Acart\Ui\Component\Listing\Column\CancelCondition;

use Magento\Framework\Data\OptionSourceInterface;
use Amasty\Acart\Model\Rule as Rule;
/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    public function toArray()
    {
        return array(
            Rule::CANCEL_CONDITION_PLACED => __("Order Placed"),
            Rule::CANCEL_CONDITION_CLICKED => __("Link from Email Clicked"),
        );
    }

    public function toOptionArray()
    {
        return [];
    }
}
