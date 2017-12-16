<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
// @codingStandardsIgnoreFile
namespace  Raveinfosys\Paypal\Model\Rewrite\Api;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
/**
 * @api
 */
class ProcessableException extends \Magento\Paypal\Model\Api\ProcessableException
{
    /**#@+
     * Error code returned by PayPal
     */
    const API_TRANSACTION_HAS_BEEN_COMPLETED = 10415;

}