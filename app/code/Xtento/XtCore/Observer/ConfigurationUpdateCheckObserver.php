<?php

/**
 * Product:       Xtento_XtCore (2.0.9)
 * ID:            489IGa+AykquMGJiZLdykETDn+04hOSECySDC/W1vyw=
 * Packaged:      2017-10-09T14:42:15+00:00
 * Last Modified: 2017-07-20T19:47:23+00:00
 * File:          app/code/Xtento/XtCore/Observer/ConfigurationUpdateCheckObserver.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\XtCore\Observer;

class ConfigurationUpdateCheckObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Xtento\XtCore\Model\System\Config\Backend\Configuration
     */
    protected $configurationCheck;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param \Xtento\XtCore\Model\System\Config\Backend\Configuration $configurationCheck
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Xtento\XtCore\Model\System\Config\Backend\Configuration $configurationCheck,
        \Magento\Framework\Registry $registry
    ) {
        $this->configurationCheck = $configurationCheck;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $updatedConfiguration = $this->registry->registry('xtento_configuration_updated');
        if ($updatedConfiguration !== null) {
            $this->registry->unregister('xtento_configuration_updated');
            $this->configurationCheck->afterUpdate($updatedConfiguration);
        }
    }
}
