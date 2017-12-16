<?php

/**
 * Product:       Xtento_GridActions (2.1.3)
 * ID:            489IGa+AykquMGJiZLdykETDn+04hOSECySDC/W1vyw=
 * Packaged:      2017-10-09T14:42:15+00:00
 * Last Modified: 2015-07-06T13:20:36+00:00
 * File:          app/code/Xtento/GridActions/Model/System/Config/Source/Actions.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\GridActions\Model\System\Config\Source;

class Actions implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    /**
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     */
    public function __construct(\Magento\Sales\Model\Order\Config $orderConfig)
    {
        $this->orderConfig = $orderConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $actions[] = ['value' => '_forceorderemail', 'label' => __('(Re-)send order email (notify Customer)')];
        $actions[] = ['value' => '_invoice_notify', 'label' => __('Invoice (notify Customer)')];
        $actions[] = ['value' => '_invoice', 'label' => __('Invoice (don\'t notify Customer)')];
        $actions[] = [
            'value' => '_invoice_forcenotification',
            'label' => __('(Re-)send invoice email (notify Customer)')
        ];
        $actions[] = ['value' => '_invoice_notify_print', 'label' => __('Invoice / Print (notify Customer)')];
        $actions[] = ['value' => '_invoice_print', 'label' => __('Invoice / Print (don\'t notify Customer)')];
        $actions[] = ['value' => '_capture', 'label' => __('Capture Payment')];
        $actions[] = ['value' => '_ship_notify', 'label' => __('Ship (notify Customer)')];
        $actions[] = ['value' => '_ship', 'label' => __('Ship (don\'t notify Customer)')];
        $actions[] = [
            'value' => '_ship_forcenotification',
            'label' => __('(Re-)send shipment email (notify Customer)')
        ];
        $actions[] = ['value' => '_ship_notify_print', 'label' => __('Ship / Print (notify Customer)')];
        $actions[] = ['value' => '_ship_print', 'label' => __('Ship / Print (don\'t notify Customer)')];
        $actions[] = ['value' => '_invoice_ship_notify', 'label' => __('Invoice / Ship (notify Customer)')];
        $actions[] = ['value' => '_invoice_ship', 'label' => __('Invoice / Ship (don\'t notify Customer)')];
        $actions[] = [
            'value' => '_invoice_ship_notify_print',
            'label' => __('Invoice / Ship / Print (notify Customer)')
        ];
        $actions[] = [
            'value' => '_invoice_ship_print',
            'label' => __('Invoice / Ship / Print (don\'t notify Customer)')
        ];
        $actions[] = [
            'value' => '_invoice_ship_complete_notify',
            'label' => __('Invoice / Ship / Complete (notify Customer)')
        ];
        $actions[] = [
            'value' => '_invoice_ship_complete',
            'label' => __('Invoice / Ship / Complete (don\'t notify Customer)')
        ];
        $actions[] = [
            'value' => '_invoice_ship_complete_notify_print',
            'label' => __('Invoice / Ship / Complete / Print (notify Customer)')
        ];
        $actions[] = [
            'value' => '_invoice_ship_complete_print',
            'label' => __('Invoice / Ship / Complete / Print (don\'t notify Customer)')
        ];
        $actions[] = ['value' => '_complete', 'label' => __('Complete Order')];
        $actions[] = ['value' => '_uncancel', 'label' => __('Uncancel Order')];

        // Add order status actions
        $orderStatuses = $this->orderConfig->getStatuses();
        foreach ($orderStatuses as $orderStatusCode => $orderStatusLabel) {
            if ($orderStatusCode === '') {
                continue;
            }
            $actions[] = [
                'value' => '_setstatus_' . $orderStatusCode,
                'label' => __('Set Order Status to \'%1\'', __($orderStatusLabel))
            ];
        }

        $actions[] = ['value' => '_delete', 'label' => __('Delete Order (canceled-only)')];
        return $actions;
    }
}
