<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
// @codingStandardsIgnoreFile
namespace Raveinfosys\Paypal\Model\Rewrite\Api;
use Magento\Payment\Model\Cart;
use Magento\Payment\Model\Method\Logger;
/**
 * NVP API wrappers model
 * @TODO: move some parts to abstract, don't hesitate to throw exceptions on api calls
 *
 * @method string getToken()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Nvp extends \Magento\Paypal\Model\Api\Nvp
{
    /**
     * Handle logical errors
     *
     * @param array $response
     * @return void
     * @throws \Magento\Paypal\Model\Api\ProcessableException|\Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _handleCallErrors($response)
    {
        $errors = $this->_extractErrorsFromResponse($response);
        if (empty($errors)) {
            return;
        }
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error['message'];
            $this->_callErrors[] = $error['code'];
        }
        $errorMessages = implode(' ', $errorMessages);
        $exceptionLogMessage = sprintf(
            'PayPal NVP gateway errors: %s Correlation ID: %s. Version: %s.',
            $errorMessages,
            isset($response['CORRELATIONID']) ? $response['CORRELATIONID'] : '',
            isset($response['VERSION']) ? $response['VERSION'] : ''
        );
        $this->_logger->critical($exceptionLogMessage);
        /**
         * The response code 10415 'Transaction has already been completed for this token'
         * must not fails place order. The old Paypal interface does not lock 'Send' button
         * it may result to re-send data.
         */
        if (in_array((string)ProcessableException::API_TRANSACTION_HAS_BEEN_COMPLETED, $this->_callErrors)) {
            return;
        }
        $exceptionPhrase = __('PayPal gateway has rejected request. %1', $errorMessages);
        /** @var \Magento\Framework\Exception\LocalizedException $exception */
        $firstError = $errors[0]['code'];
        $exception = $this->_isProcessableError($firstError)
            ? $this->_processableExceptionFactory->create(
                ['phrase' => $exceptionPhrase, 'code' => $firstError]
            )
            : $this->_frameworkExceptionFactory->create(
                ['phrase' => $exceptionPhrase]
            );
        throw $exception;
    }
     /**
     * Adopt specified address object to be compatible with Magento
     *
     * @param \Magento\Framework\DataObject $address
     * @return void
     */
    protected function _applyStreetAndRegionWorkarounds(\Magento\Framework\DataObject $address)
    {
        // merge street addresses into 1
        if ($address->getData('street2') !== null) {
            $address->setStreet(implode("\n", [$address->getData('street'), $address->getData('street2')]));
            $address->unsetData('street2');
        }
        // attempt to fetch region_id from directory
        if ($address->getCountryId() && $address->getRegion()) {
            $regions = $this->_countryFactory->create()->loadByCode(
                $address->getCountryId()
            )->getRegionCollection()->addRegionCodeOrNameFilter(
                $address->getRegion()
            )->setPageSize(
                1
            );
            foreach ($regions as $region) {
                $address->setRegionId($region->getId());
                $address->setExportedKeys(array_merge($address->getExportedKeys(), ['region_id']));
                break;
            }
        }
    }
   
}