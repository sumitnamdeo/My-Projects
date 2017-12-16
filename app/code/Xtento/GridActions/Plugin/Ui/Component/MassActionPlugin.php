<?php

/**
 * Product:       Xtento_GridActions (2.1.3)
 * ID:            489IGa+AykquMGJiZLdykETDn+04hOSECySDC/W1vyw=
 * Packaged:      2017-10-09T14:42:15+00:00
 * Last Modified: 2017-05-30T12:11:54+00:00
 * File:          app/code/Xtento/GridActions/Plugin/Ui/Component/MassActionPlugin.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\GridActions\Plugin\Ui\Component;

use Magento\Ui\Component\MassAction;

/**
 * Class MassActionPlugin
 * @package Xtento\GridActions\Ui\Plugin\Component
 */
class MassActionPlugin
{
    /**
     * @var \Xtento\GridActions\Helper\Module
     */
    protected $moduleHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * Adminhtml data
     *
     * @var \Magento\Backend\Helper\Data
     */
    protected $adminhtmlData = null;

    /**
     * @var \Xtento\GridActions\Model\System\Config\Source\Actions
     */
    protected $actionsSource;

    /**
     * @param \Xtento\GridActions\Helper\Module $moduleHelper
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Magento\Backend\Helper\Data $adminhtmlData
     * @param \Xtento\GridActions\Model\System\Config\Source\Actions $actionsSource
     */
    public function __construct(
        \Xtento\GridActions\Helper\Module $moduleHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Backend\Helper\Data $adminhtmlData,
        \Xtento\GridActions\Model\System\Config\Source\Actions $actionsSource
    ) {
        $this->moduleHelper = $moduleHelper;
        $this->request = $request;
        $this->scopeConfig = $config;
        $this->registry = $registry;
        $this->authorization = $authorization;
        $this->adminhtmlData = $adminhtmlData;
        $this->actionsSource = $actionsSource;
    }

    /**
     * Add massactions to the Sales > Orders grid.
     * Why not via sales_order_grid.xml? Because then you cannot select the actions which should be shown from
     * the Magento admin, this is required so admins can adjust the actions via the configuration.
     *
     * @param MassAction $subject
     * @param string $interceptedOutput
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPrepare(MassAction $subject, $interceptedOutput)
    {
        if ($subject->getContext()->getNamespace() !== 'sales_order_grid') {
            return;
        }
        if (!$this->moduleHelper->isModuleEnabled()) {
            return;
        }
        if ($this->registry->registry('xtDisabled') !== false) {
            return;
        }

        $config = $subject->getData('config');

        $enabledActions = explode(',', $this->scopeConfig->getValue('gridactions/general/actions'));
        foreach ($this->actionsSource->toOptionArray() as $action) {
            $actionCode = $action['value'];

            if (!in_array($actionCode, $enabledActions) && isset($enabledActions[0]) && $enabledActions[0] !== 'all') {
                continue;
            }
            if ($this->_isAllowed($actionCode)) {
                $config['actions'][] = [
                    'type' => $actionCode,
                    'label' => __($action['label']),
                    //'confirm' => __("Are you sure?"),
                    'url' => $this->adminhtmlData->getUrl(
                        'gridactions/grid/mass',
                        [
                            'actions' => $actionCode,
                            'namespace' => $subject->getContext()->getNamespace()
                        ]
                    ),
                    'callback' => [
                        'provider' => 'sales_order_grid.sales_order_grid.extendedGrid',
                        'target' => 'bulkActionCallback'
                    ]
                ];
            }
        }

        $subject->setData('config', $config);
    }

    protected function _isAllowed($actionCode)
    {
        if (stristr($actionCode, 'invoice') && !$this->authorization->isAllowed('Xtento_GridActions::invoice')) {
            return false;
        }
        if (stristr($actionCode, 'ship') && !$this->authorization->isAllowed('Xtento_GridActions::ship')) {
            return false;
        }
        if (stristr($actionCode, 'capture') && !$this->authorization->isAllowed('Xtento_GridActions::capture')) {
            return false;
        }
        if (stristr($actionCode, 'print') && !$this->authorization->isAllowed('Xtento_GridActions::print')) {
            return false;
        }
        if (stristr($actionCode, 'complete') && !$this->authorization->isAllowed('Xtento_GridActions::complete')) {
            return false;
        }
        if (stristr($actionCode, 'notify') && !$this->authorization->isAllowed('Xtento_GridActions::email')) {
            return false;
        }
        if (stristr($actionCode, 'setstatus') && !$this->authorization->isAllowed('Xtento_GridActions::changestatus')) {
            return false;
        }
        if (stristr($actionCode, 'delete') && !$this->authorization->isAllowed('Xtento_GridActions::delete')) {
            return false;
        }
        return true;
    }
}
