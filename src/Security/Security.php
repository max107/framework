<?php

namespace Mindy\Security;

use Exception;
use Mindy\Base\Mindy;
use Mindy\Helper\Security as SecurityHelper;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;

/**
 * Class SecurityManager
 * @package Mindy\Security
 */
class Security
{
    use Configurator, Accessors;

    const STATE_VALIDATION_KEY = 'Security.validationkey';
    const STATE_ENCRYPTION_KEY = 'Security.encryptionkey';

    /**
     * @var string the name of the hashing algorithm to be used by {@link computeHMAC}.
     * See {@link http://php.net/manual/en/function.hash-algos.php hash-algos} for the list of possible
     * hash algorithms. Note that if you are using PHP 5.1.1 or below, you can only use 'sha1' or 'md5'.
     *
     * Defaults to 'sha1', meaning using SHA1 hash algorithm.
     * @since 1.1.3
     */
    public $hashAlgorithm = 'sha1';
    /**
     * @var mixed the name of the crypt algorithm to be used by {@link encrypt} and {@link decrypt}.
     * This will be passed as the first parameter to {@link http://php.net/manual/en/function.mcrypt-module-open.php mcrypt_module_open}.
     *
     * This property can also be configured as an array. In this case, the array elements will be passed in order
     * as parameters to mcrypt_module_open. For example, <code>array('rijndael-256', '', 'ofb', '')</code>.
     *
     * Defaults to 'des', meaning using DES crypt algorithm.
     * @since 1.1.3
     */
    public $cryptAlgorithm = 'des';

    private $_validationKey;
    private $_encryptionKey;

    /**
     * @return string a randomly generated private key.
     * @deprecated in favor of {@link generateRandomString()} since 1.1.14. Never use this method.
     */
    protected function generateRandomKey()
    {
        return SecurityHelper::generateRandomString(32);
    }

    public function generateRandomBytes($length, $cryptographicallyStrong = true)
    {
        return SecurityHelper::generateRandomBytes($length, $cryptographicallyStrong);
    }

    /**
     * @return string the private key used to generate HMAC.
     * If the key is not explicitly set, a random one is generated and returned.
     * @throws Exception in case random string cannot be generated.
     */
    public function getValidationKey()
    {
        if ($this->_validationKey !== null) {
            return $this->_validationKey;
        } else {
            if (($key = Mindy::app()->getGlobalState(self::STATE_VALIDATION_KEY)) !== null) {
                $this->setValidationKey($key);
            } else {
                if (($key = $this->generateRandomString(32, true)) === false) {
                    if (($key = $this->generateRandomString(32, false)) === false) {
                        throw new Exception('SecurityManager::generateRandomString() cannot generate random string in the current environment');
                    }
                }
                $this->setValidationKey($key);
                Mindy::app()->setGlobalState(self::STATE_VALIDATION_KEY, $key);
            }
            return $this->_validationKey;
        }
    }

    /**
     * @param string $value the key used to generate HMAC
     * @throws Exception if the key is empty
     */
    public function setValidationKey($value)
    {
        if (!empty($value)) {
            $this->_validationKey = $value;
        } else {
            throw new Exception('SecurityManager.validationKey cannot be empty');
        }
    }

    /**
     * @return string the private key used to encrypt/decrypt data.
     * If the key is not explicitly set, a random one is generated and returned.
     * @throws Exception in case random string cannot be generated.
     */
    public function getEncryptionKey()
    {
        if ($this->_encryptionKey !== null) {
            return $this->_encryptionKey;
        } else {
            if (($key = Mindy::app()->getGlobalState(self::STATE_ENCRYPTION_KEY)) !== null) {
                $this->setEncryptionKey($key);
            } else {
                if (($key = $this->generateRandomString(32, true)) === false) {
                    if (($key = $this->generateRandomString(32, false)) === false) {
                        throw new Exception('SecurityManager::generateRandomString() cannot generate random string in the current environment');
                    }
                }
                $this->setEncryptionKey($key);
                Mindy::app()->setGlobalState(self::STATE_ENCRYPTION_KEY, $key);
            }
            return $this->_encryptionKey;
        }
    }

    /**
     * @param string $value the key used to encrypt/decrypt data.
     * @throws Exception if the key is empty
     */
    public function setEncryptionKey($value)
    {
        if (!empty($value)) {
            $this->_encryptionKey = $value;
        } else {
            throw new Exception('SecurityManager.encryptionKey cannot be empty');
        }
    }

    /**
     * Encrypts data.
     * @param string $data data to be encrypted.
     * @param string $key the decryption key. This defaults to null, meaning using {@link getEncryptionKey EncryptionKey}.
     * @return string the encrypted data
     * @throws Exception if PHP Mcrypt extension is not loaded
     */
    public function encrypt($data, $key = null)
    {
        $module = SecurityHelper::openCryptModule($this->cryptAlgorithm);
        return SecurityHelper::encrypt($module, $data, $key === null ? md5($this->getEncryptionKey()) : $key);
    }

    /**
     * Decrypts data
     * @param string $data data to be decrypted.
     * @param string $key the decryption key. This defaults to null, meaning using {@link getEncryptionKey EncryptionKey}.
     * @return string the decrypted data
     * @throws Exception if PHP Mcrypt extension is not loaded
     */
    public function decrypt($data, $key = null)
    {
        $module = SecurityHelper::openCryptModule($this->cryptAlgorithm);
        return SecurityHelper::decrypt($module, $data, $key === null ? md5($this->getEncryptionKey()) : $key);
    }

    /**
     * Prefixes data with an HMAC.
     * @param string $data data to be hashed.
     * @param string $key the private key to be used for generating HMAC. Defaults to null, meaning using {@link validationKey}.
     * @return string data prefixed with HMAC
     */
    public function hashData($data, $key = null)
    {
        return $this->computeHMAC($data, $key) . $data;
    }

    /**
     * Validates if data is tampered.
     * @param string $data data to be validated. The data must be previously
     * generated using {@link hashData()}.
     * @param string $key the private key to be used for generating HMAC. Defaults to null, meaning using {@link validationKey}.
     * @param null $hashAlgorithm
     * @return string the real data with HMAC stripped off. False if the data
     * is tampered.
     * @throws Exception
     */
    public function validateData($data, $key = null, $hashAlgorithm = null)
    {
        return SecurityHelper::validateData(
            $data,
            $key ? $key : $this->getValidationKey(),
            $hashAlgorithm ? $hashAlgorithm : $this->hashAlgorithm
        );
    }

    /**
     * Computes the HMAC for the data with {@link getValidationKey validationKey}. This method has been made public
     * since 1.1.14.
     * @param string $data data to be generated HMAC.
     * @param string|null $key the private key to be used for generating HMAC. Defaults to null, meaning using
     * {@link validationKey} value.
     * @param string|null $hashAlgorithm the name of the hashing algorithm to be used.
     * See {@link http://php.net/manual/en/function.hash-algos.php hash-algos} for the list of possible
     * hash algorithms. Note that if you are using PHP 5.1.1 or below, you can only use 'sha1' or 'md5'.
     * Defaults to null, meaning using {@link hashAlgorithm} value.
     * @return string the HMAC for the data.
     * @throws Exception on unsupported hash algorithm given.
     */
    public function computeHMAC($data, $key = null, $hashAlgorithm = null)
    {
        return SecurityHelper::computeHMAC(
            $data,
            $key ? $key : $this->getValidationKey(),
            $hashAlgorithm ? $hashAlgorithm : $this->hashAlgorithm
        );
    }

    /**
     * Generate a random ASCII string. Generates only [0-9a-zA-z_~] characters which are all
     * transparent in raw URL encoding.
     * @param integer $length length of the generated string in characters.
     * @param boolean $cryptographicallyStrong set this to require cryptographically strong randomness.
     * @return string|boolean random string or false in case it cannot be generated.
     * @since 1.1.14
     */
    public function generateRandomString($length, $cryptographicallyStrong = true)
    {
        return SecurityHelper::generateRandomString($length, $cryptographicallyStrong);
    }
}
