<?php

/**
 * Product:       Xtento_XtCore (2.0.9)
 * ID:            489IGa+AykquMGJiZLdykETDn+04hOSECySDC/W1vyw=
 * Packaged:      2017-10-09T14:42:15+00:00
 * Last Modified: 2017-07-20T19:42:07+00:00
 * File:          app/code/Xtento/XtCore/Helper/Shipping.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\XtCore\Helper;

class Shipping extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Core store config
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Shipping constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
        $this->scopeConfig = $context->getScopeConfig();
    }

    public function determineCarrierTitle($carrierCode, $shippingMethod = '', $storeId = null)
    {
        if ($carrierCode == 'custom') {
            $carrierTitle = (empty($shippingMethod)) ? __('Custom') : $shippingMethod;
        } else {
            $carrierTitle = $this->scopeConfig->getValue(
                'carriers/' . $carrierCode . '/title',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if (empty($carrierTitle)) {
                // Try to get carrier from XTENTO custom carrier trackers extension
                $carrierTitle = $this->scopeConfig->getValue(
                    'customtrackers/' . $carrierCode . '/title',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                );
            }
        }
        return $carrierTitle;
    }
}
