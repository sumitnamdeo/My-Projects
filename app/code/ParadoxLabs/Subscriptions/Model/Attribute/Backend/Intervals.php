<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <magento@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\Subscriptions\Model\Attribute\Backend;

/**
 * Intervals Class
 */
class Intervals extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    /**
     * Validate subscription interval(s)
     *
     * @param \Magento\Framework\DataObject $object
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate($object)
    {
        parent::validate($object);

        $attrCode = $this->getAttribute()->getAttributeCode();
        $value = $object->getData($attrCode);

        /**
         * Ensure that comma-separated values are positive-numeric only.
         */
        $values = array_filter(explode(',', str_replace(' ', '', $value)));
        foreach ($values as $value) {
            if (preg_match("/[^0-9]/", $value)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __(
                        'Attribute "%1" contains invalid interval "%2". Please only enter positive numbers.',
                        $attrCode,
                        $value
                    )
                );
            }
        }

        // Repack after validation to ensure there are no duplicate or empty values left over.
        $object->setData($attrCode, implode(',', array_unique($values)));

        return true;
    }

    /**
     * Clean up values on save
     *
     * @param \Magento\Framework\DataObject  $object
     * @return \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
     */
    public function beforeSave($object)
    {
        $this->validate($object);

        return parent::beforeSave($object);
    }
}
