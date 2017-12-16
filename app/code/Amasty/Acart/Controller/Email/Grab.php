<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Acart
 */

namespace Amasty\Acart\Controller\Email;

use Magento\Checkout\Model\Cart as CustomerCart;

class Grab extends \Amasty\Acart\Controller\Email
{
    protected $_cart;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CustomerCart $cart
    ){
        $this->_cart = $cart;

        return parent::__construct($context);
    }

    public function execute()
    {
        $email = $this->getRequest()->getParam('email');

        if ($this->_cart->getQuote()){

            $quoteEmail = $this->_objectManager->create('Amasty\Acart\Model\QuoteEmail')
                ->load($this->_cart->getQuote()->getId(), 'quote_id')
                ->addData(array(
                    'quote_id' => $this->_cart->getQuote()->getId(),
                    'customer_email' => $email
                ))->save();
        }
    }


}