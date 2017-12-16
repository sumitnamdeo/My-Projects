<?php

/**
 * Customer method model
 *
 * @category    Raveinfosys
 * @package     Raveinfosys_Customer
 * @author      Raveinfosys Inc.
 */
namespace Raveinfosys\Customer\Model;

use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\State\UserLockedException; 

class Authentication extends \Magento\Customer\Model\Authentication
{
    /**
     * {@inheritdoc}
     */
    public function authenticate($customerId, $password)
    {
        $customerSecure = $this->customerRegistry->retrieveSecureData($customerId);
        $hash = $customerSecure->getPasswordHash();
        if (!$this->encryptor->validateHash($password, $hash)) {

            if ($this->validateWPPassword($password, $hash)) {
                return true;
            }

            $this->processAuthenticationFailure($customerId);
            if ($this->isLocked($customerId)) {
                throw new UserLockedException(__('The account is locked.'));
            }
            throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
        }
        return true;
    }

    public function validateWPPassword($password, $hash)
    {
        if($this->md5WPPassword($password, $hash)) {
            return $this->md5WPPassword($password, $hash);
        }

        return $this->passwordHashWPPassword($password, $hash);

    }

    public function md5WPPassword($password, $hash)
    {
        $length = strlen($password);

        if ($length !== strlen($hash)) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $length; $i++) {
            $result |= ord($password[$i]) ^ ord($hash[$i]);
        }

        return $result === 0;
    }

    public function passwordHashWPPassword($password, $hash)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $wpHasher = $objectManager->create('Raveinfosys\Customer\Model\WPPasswordHash');
        return $wpHasher->CheckPassword($password, $hash);
    }
}