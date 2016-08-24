<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 24/08/16
 * Time: 11:34
 */

namespace Mindy\Helper;
use Exception;

/**
 * Class Security
 * @package Mindy\Helper
 */
class Security
{
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
    public static function computeHMAC($data, $key, $hashAlgorithm)
    {
        if (function_exists('hash_hmac')) {
            return hash_hmac($hashAlgorithm, $data, $key);
        }

        if (0 === strcasecmp($hashAlgorithm, 'sha1')) {
            $pack = 'H40';
            $func = 'sha1';
        } elseif (0 === strcasecmp($hashAlgorithm, 'md5')) {
            $pack = 'H32';
            $func = 'md5';
        } else {
            throw new Exception('Only SHA1 and MD5 hashing algorithms are supported when using PHP 5.1.1 or below.');
        }

        if (self::strlen($key) > 64) {
            $key = pack($pack, $func($key));
        }

        if (self::strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }

        $key = self::substr($key, 0, 64);
        return $func((str_repeat(chr(0x5C), 64) ^ $key) . pack($pack, $func((str_repeat(chr(0x36), 64) ^ $key) . $data)));
    }

    /**
     * Validates if data is tampered.
     * @param string $data data to be validated. The data must be previously
     * generated using {@link hashData()}.
     * @param string $key the private key to be used for generating HMAC. Defaults to null, meaning using {@link validationKey}.
     * @return string the real data with HMAC stripped off. False if the data
     * is tampered.
     */
    public static function validateData($data, $key, $hashAlgorithm)
    {
        $len = self::strlen(self::computeHMAC('test', $key, $hashAlgorithm));
        if (self::strlen($data) >= $len) {
            $hmac = self::substr($data, 0, $len);
            $data2 = self::substr($data, $len, self::strlen($data));
            return $hmac === self::computeHMAC($data2, $key, $hashAlgorithm) ? $data2 : false;
        } else {
            return false;
        }
    }
    
    /**
     * Returns the length of the given string.
     * If available uses the multibyte string function mb_strlen.
     * @param string $string the string being measured for length
     * @return integer the length of the string
     */
    private static function strlen($string)
    {
        return extension_loaded('mbstring') ? mb_strlen($string, '8bit') : strlen($string);
    }

    /**
     * Returns the portion of string specified by the start and length parameters.
     * If available uses the multibyte string function mb_substr
     * @param string $string the input string. Must be one character or longer.
     * @param integer $start the starting position
     * @param integer $length the desired portion length
     * @return string the extracted part of string, or FALSE on failure or an empty string.
     */
    private static function substr($string, $start, $length)
    {
        return extension_loaded('mbstring') ? mb_substr($string, $start, $length, '8bit') : substr($string, $start, $length);
    }

    /**
     * Generate a random ASCII string. Generates only [0-9a-zA-z_~] characters which are all
     * transparent in raw URL encoding.
     * @param integer $length length of the generated string in characters.
     * @param boolean $cryptographicallyStrong set this to require cryptographically strong randomness.
     * @return string|boolean random string or false in case it cannot be generated.
     * @since 1.1.14
     */
    public static function generateRandomString($length, $cryptographicallyStrong = true)
    {
        if (($randomBytes = self::generateRandomBytes($length + 2, $cryptographicallyStrong)) !== false) {
            return strtr(self::substr(base64_encode($randomBytes), 0, $length), array('+' => '_', '/' => '~'));
        }
        return false;
    }

    /**
     * Generate a pseudo random block of data using several sources. On some systems this may be a bit
     * better than PHP's {@link mt_rand} built-in function, which is not really random.
     * @return string of 64 pseudo random bytes.
     * @since 1.1.14
     */
    public static function generatePseudoRandomBlock()
    {
        $bytes = '';

        if (function_exists('openssl_random_pseudo_bytes')
            && ($bytes = openssl_random_pseudo_bytes(512)) !== false
            && self::strlen($bytes) >= 512
        ) {
            return self::substr($bytes, 0, 512);
        }

        for ($i = 0; $i < 32; ++$i)
            $bytes .= pack('S', mt_rand(0, 0xffff));

        // On UNIX and UNIX-like operating systems the numerical values in `ps`, `uptime` and `iostat`
        // ought to be fairly unpredictable. Gather the non-zero digits from those.
        foreach (['ps', 'uptime', 'iostat'] as $command) {
            @exec($command, $commandResult, $retVal);
            if (is_array($commandResult) && !empty($commandResult) && $retVal == 0) {
                $bytes .= preg_replace('/[^1-9]/', '', implode('', $commandResult));
            }
        }

        // Gather the current time's microsecond part. Note: this is only a source of entropy on
        // the first call! If multiple calls are made, the entropy is only as much as the
        // randomness in the time between calls.
        $bytes .= self::substr(microtime(), 2, 6);

        // Concatenate everything gathered, mix it with sha512. hash() is part of PHP core and
        // enabled by default but it can be disabled at compile time but we ignore that possibility here.
        return hash('sha512', $bytes, true);
    }

    /**
     * Generates a string of random bytes.
     * @param integer $length number of random bytes to be generated.
     * @param boolean $cryptographicallyStrong whether to fail if a cryptographically strong
     * result cannot be generated. The method attempts to read from a cryptographically strong
     * pseudorandom number generator (CS-PRNG), see
     * {@link https://en.wikipedia.org/wiki/Cryptographically_secure_pseudorandom_number_generator#Requirements Wikipedia}.
     * However, in some runtime environments, PHP has no access to a CS-PRNG, in which case
     * the method returns false if $cryptographicallyStrong is true. When $cryptographicallyStrong is false,
     * the method always returns a pseudorandom result but may fall back to using {@link generatePseudoRandomBlock}.
     * This method does not guarantee that entropy, from sources external to the CS-PRNG, was mixed into
     * the CS-PRNG state between each successive call. The caller can therefore expect non-blocking
     * behavior, unlike, for example, reading from /dev/random on Linux, see
     * {@link http://eprint.iacr.org/2006/086.pdf Gutterman et al 2006}.
     * @return boolean|string generated random binary string or false on failure.
     * @since 1.1.14
     */
    public static function generateRandomBytes($length, $cryptographicallyStrong = true)
    {
        $bytes = '';
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if (self::strlen($bytes) >= $length && ($strong || !$cryptographicallyStrong)) {
                return self::substr($bytes, 0, $length);
            }
        }

        if (function_exists('mcrypt_create_iv') &&
            ($bytes = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM)) !== false &&
            self::strlen($bytes) >= $length
        ) {
            return self::substr($bytes, 0, $length);
        }

        if (($file = @fopen('/dev/urandom', 'rb')) !== false &&
            ($bytes = @fread($file, $length)) !== false &&
            (fclose($file) || true) &&
            self::strlen($bytes) >= $length
        ) {
            return self::substr($bytes, 0, $length);
        }

        if (self::strlen($bytes) >= $length) {
            return self::substr($bytes, 0, $length);
        }

        if ($cryptographicallyStrong) {
            return false;
        }

        while (self::strlen($bytes) < $length) {
            $bytes .= self::generatePseudoRandomBlock();
        }
        return self::substr($bytes, 0, $length);
    }

    /**
     * Decrypts data
     * @param string $data data to be decrypted.
     * @param string $key the decryption key. This defaults to null, meaning using {@link getEncryptionKey EncryptionKey}.
     * @return string the decrypted data
     * @throws Exception if PHP Mcrypt extension is not loaded
     */
    public static function decrypt($module, $data, $key)
    {
        $key = self::substr($key, 0, mcrypt_enc_get_key_size($module));
        $ivSize = mcrypt_enc_get_iv_size($module);
        $iv = self::substr($data, 0, $ivSize);
        mcrypt_generic_init($module, $key, $iv);
        $decrypted = mdecrypt_generic($module, self::substr($data, $ivSize, self::strlen($data)));
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        return rtrim($decrypted, "\0");
    }

    /**
     * Encrypts data.
     * @param $module
     * @param string $data data to be encrypted.
     * @param string $key the decryption key. This defaults to null, meaning using {@link getEncryptionKey EncryptionKey}.
     * @return string the encrypted data
     */
    public static function encrypt($module, $data, $key)
    {
        $key = self::substr($key, 0, mcrypt_enc_get_key_size($module));
        srand();
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($module), MCRYPT_RAND);
        mcrypt_generic_init($module, $key, $iv);
        $encrypted = $iv . mcrypt_generic($module, $data);
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        return $encrypted;
    }

    /**
     * Opens the mcrypt module with the configuration specified in {@link cryptAlgorithm}.
     * @throws Exception if failed to initialize the mcrypt module or PHP mcrypt extension
     * @return resource the mycrypt module handle.
     * @since 1.1.3
     */
    public static function openCryptModule($cryptAlgorithm)
    {
        if (extension_loaded('mcrypt')) {
            if (is_array($cryptAlgorithm)) {
                $module = @call_user_func_array('mcrypt_module_open', $cryptAlgorithm);
            } else {
                $module = @mcrypt_module_open($cryptAlgorithm, '', MCRYPT_MODE_CBC, '');
            }

            if ($module === false) {
                throw new Exception('Failed to initialize the mcrypt module');
            }

            return $module;
        } else {
            throw new Exception('SecurityManager requires PHP mcrypt extension to be loaded in order to use data encryption feature');
        }
    }
}