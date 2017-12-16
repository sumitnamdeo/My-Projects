<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OrderEditor\Block\Adminhtml\Sales\Order\Edit;

use Magento\Framework\View\Element\Template;

class Wrapper extends Template
{
    /**
     * @return string
     */
    public function getJsonParamsItems()
    {
        $data = [
            'loadFormUrl' => $this->getUrl('ordereditor/form/load'),
            'updateUrl' => $this->getUrl('ordereditor/edit/items'),
            'isAllowed' => true
        ];

        return json_encode($data);
    }

    /**
     * @return string
     */
    public function getJsonParamsAddress()
    {
        $data = [
            'loadFormUrl' => $this->getUrl('ordereditor/form/load'),
            'updateUrl' => $this->getUrl('ordereditor/edit/address'),
            'isAllowed' => true
        ];

        return json_encode($data);
    }

    /**
     * @return string
     */
    public function getJsonParamsShipping()
    {
        $data = [
            'loadFormUrl' => $this->getUrl('ordereditor/form/load'),
            'updateUrl' => $this->getUrl('ordereditor/edit/shipping'),
            'isAllowed' => true
        ];

        return json_encode($data);
    }
}
