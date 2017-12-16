<?php

/**
 * Product:       Xtento_XtCore (2.0.9)
 * ID:            489IGa+AykquMGJiZLdykETDn+04hOSECySDC/W1vyw=
 * Packaged:      2017-10-09T14:42:15+00:00
 * Last Modified: 2017-07-20T19:40:44+00:00
 * File:          app/code/Xtento/XtCore/Block/View/Element/Message/Renderer/NoescapeRenderer.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\XtCore\Block\View\Element\Message\Renderer;

use Magento\Framework\Message\MessageInterface;

class NoescapeRenderer implements \Magento\Framework\View\Element\Message\Renderer\RendererInterface
{
    /**
     * complex_renderer
     */
    const CODE = 'noescape_renderer';

    /**
     * Renders complex message, no escaping
     *
     * @param MessageInterface $message
     * @param array $initializationData
     * @return string
     */
    public function render(MessageInterface $message, array $initializationData)
    {
        return $message->getText();
    }
}
