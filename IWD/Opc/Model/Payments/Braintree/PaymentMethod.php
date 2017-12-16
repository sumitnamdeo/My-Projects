<?php

namespace IWD\Opc\Model\Payments\Braintree;

use \Magento\Braintree\Model\PaymentMethod as brainTreeConfig;

class PaymentMethod extends brainTreeConfig{

    protected function getChannel()
    {
        return 'Magento-IWD';
    }
}