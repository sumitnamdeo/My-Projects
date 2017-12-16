<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author        Ryan Hoerr <support@paradoxlabs.com>
 * @license       http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Model;

/**
 * CardAdditional Class
 *
 * Note: Not all keys are used by all methods. Added all keys used by any tokenbase method at time of implementation.
 */
class CardAdditional extends \Magento\Framework\Model\AbstractExtensibleModel implements
    \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface
{
    /**
     * @return mixed
     */
    public function getCcBin()
    {
        return $this->getData('cc_bin');
    }

    /**
     * @param mixed $ccBin
     * @return mixed
     */
    public function setCcBin($ccBin)
    {
        return $this->setData('cc_bin', $ccBin);
    }

    /**
     * @return mixed
     */
    public function getCcExpMonth()
    {
        return $this->getData('cc_exp_month');
    }

    /**
     * @param mixed $ccExpMonth
     * @return mixed
     */
    public function setCcExpMonth($ccExpMonth)
    {
        return $this->setData('cc_exp_month', $ccExpMonth);
    }

    /**
     * @return mixed
     */
    public function getCcExpYear()
    {
        return $this->getData('cc_exp_year');
    }

    /**
     * @param mixed $ccExpYear
     * @return mixed
     */
    public function setCcExpYear($ccExpYear)
    {
        return $this->setData('cc_exp_year', $ccExpYear);
    }

    /**
     * @return mixed
     */
    public function getCcLast4()
    {
        return $this->getData('cc_last4');
    }

    /**
     * @param mixed $ccLast4
     * @return mixed
     */
    public function setCcLast4($ccLast4)
    {
        return $this->setData('cc_last4', $ccLast4);
    }

    /**
     * @return mixed
     */
    public function getCcType()
    {
        return $this->getData('cc_type');
    }

    /**
     * @param mixed $ccType
     * @return mixed
     */
    public function setCcType($ccType)
    {
        return $this->setData('cc_type', $ccType);
    }

    /**
     * @return mixed
     */
    public function getCcCountry()
    {
        return $this->getData('cc_country');
    }

    /**
     * @param $ccCountry
     * @return mixed
     */
    public function setCcCountry($ccCountry)
    {
        return $this->setData('cc_country', $ccCountry);
    }

    /**
     * @return mixed
     */
    public function getEcheckAccountName()
    {
        return $this->getData('echeck_account_name');
    }

    /**
     * @param mixed $echeckAccountName
     * @return mixed
     */
    public function setEcheckAccountName($echeckAccountName)
    {
        return $this->setData('echeck_account_name', $echeckAccountName);
    }

    /**
     * @return mixed
     */
    public function getEcheckAccountNumberLast4()
    {
        return $this->getData('echeck_account_number_last4');
    }

    /**
     * @param mixed $echeckAccountNumberLast4
     * @return mixed
     */
    public function setEcheckAccountNumberLast4($echeckAccountNumberLast4)
    {
        return $this->setData('echeck_account_number_last4', $echeckAccountNumberLast4);
    }

    /**
     * @return mixed
     */
    public function getEcheckAccountType()
    {
        return $this->getData('echeck_account_type');
    }

    /**
     * @param mixed $echeckAccountType
     * @return mixed
     */
    public function setEcheckAccountType($echeckAccountType)
    {
        return $this->setData('echeck_account_type', $echeckAccountType);
    }

    /**
     * @return mixed
     */
    public function getEcheckBankName()
    {
        return $this->getData('echeck_bank_name');
    }

    /**
     * @param mixed $echeckBankName
     * @return mixed
     */
    public function setEcheckBankName($echeckBankName)
    {
        return $this->setData('echeck_bank_name', $echeckBankName);
    }

    /**
     * @return mixed
     */
    public function getObject()
    {
        return $this->getData('object');
    }

    /**
     * @param $object
     * @return mixed
     */
    public function setObject($object)
    {
        return $this->setData('object', $object);
    }

    /**
     * @return mixed
     */
    public function getFunding()
    {
        return $this->getData('funding');
    }

    /**
     * @param $funding
     * @return mixed
     */
    public function setFunding($funding)
    {
        return $this->setData('funding', $funding);
    }

    /**
     * @return mixed
     */
    public function getAddressLine1Check()
    {
        return $this->getData('address_line1_check');
    }

    /**
     * @param $addressLine1Check
     * @return mixed
     */
    public function setAddressLine1Check($addressLine1Check)
    {
        return $this->setData('address_line1_check', $addressLine1Check);
    }

    /**
     * @return mixed
     */
    public function getCvcCheck()
    {
        return $this->getData('cvc_check');
    }

    /**
     * @param $cvcCheck
     * @return mixed
     */
    public function setCvcCheck($cvcCheck)
    {
        return $this->setData('cvc_check', $cvcCheck);
    }

    /**
     * @return mixed
     */
    public function getFingerprint()
    {
        return $this->getData('fingerprint');
    }

    /**
     * @param $fingerprint
     * @return mixed
     */
    public function setFingerprint($fingerprint)
    {
        return $this->setData('fingerprint', $fingerprint);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \ParadoxLabs\TokenBase\Api\Data\CardAdditionalExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardAdditionalExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \ParadoxLabs\TokenBase\Api\Data\CardAdditionalExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
