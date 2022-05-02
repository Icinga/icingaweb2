<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Crypt;

use UnexpectedValueException;
use RuntimeException;

/**
 * Data encryption and decryption using symmetric algorithm
 *
 * # Example Usage
 *
 * ```php
 *
 * // Encryption
 * $encryptedData = (new AesCrypt())->encrypt($data); // Accepts a string
 *
 *
 * // Encrypt and encode to Base64
 * $encryptedData = (new AesCrypt())->encryptToBase64($data); // Accepts a string
 *
 *
 * // Decryption
 * $aesCrypt = (new AesCrypt())
 *          ->setTag($tag)  // if exists
 *          ->setIV($iv)
 *          ->setKey($key);
 *
 * $decryptedData = $aesCrypt->decrypt($data);
 *
 * // Decode from Base64 and decrypt
 * $aesCrypt = (new AesCrypt())
 *          ->setTag($tag)
 *          ->setIV($iv)
 *          ->setKey($key);
 *
 * $decryptedData = $aesCrypt->decryptFromBase64($data);
 * ```
 *
 */
class AesCrypt
{
    /** @var array The list of cipher methods */
    const METHODS = [
        'aes-256-gcm',
        'aes-256-cbc',
        'aes-256-ctr'
    ];

    /** @var string The encryption key */
    private $key;

    /** @var int The length of the key */
    private $keyLength;

    /** @var string The initialization vector which is not NULL */
    private $iv;

    /** @var string The authentication tag which is passed by reference when using AEAD cipher mode */
    private $tag;

    /** @var string The cipher method */
    private $method;

    public function __construct($keyLength = 128)
    {
        $this->keyLength = $keyLength;
    }

    /**
     * Set the method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get the method
     *
     * @return string
     */
    public function getMethod()
    {
        if ($this->method === null) {
            $this->method = $this->getSupportedMethod();
        }

        return $this->method;
    }

    /**
     * Get supported method
     *
     * @return string
     *
     * @throws RuntimeException If none of the methods listed in the METHODS array is available
     */
    protected function getSupportedMethod()
    {
        $availableMethods = openssl_get_cipher_methods();
        $methods = self::METHODS;

        if (! $this->isAuthenticatedEncryptionSupported()) {
            unset($methods[0]);
        }

        foreach ($methods as $method) {
            if (in_array($method, $availableMethods)) {
                return $method;
            }
        }

        throw new RuntimeException('No supported method found');
    }

    /**
     * Set the key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get the key
     *
     * @return string
     *
     */
    public function getKey()
    {
        if (empty($this->key)) {
            $this->key = random_bytes($this->keyLength);
        }

        return $this->key;
    }

    /**
     * Set the IV
     *
     * @return $this
     */
    public function setIV($iv)
    {
        $this->iv = $iv;

        return $this;
    }

    /**
     * Get the IV
     *
     * @return string
     *
     */
    public function getIV()
    {
        if (empty($this->iv)) {
            $len = openssl_cipher_iv_length($this->getMethod());
            $this->iv = random_bytes($len);
        }

        return $this->iv;
    }

    /**
     * Set the Tag
     *
     * @return $this
     *
     * @throws RuntimeException If a tag is available but authenticated encryption (AE) is not supported.
     *
     * @throws UnexpectedValueException If tag length is less then 16
     */
    public function setTag($tag)
    {
        if (! $this->isAuthenticatedEncryptionSupported()) {
            throw new RuntimeException(sprintf(
                "The given decryption method is not supported in php version '%s'",
                PHP_VERSION
            ));
        }

        if (strlen($tag) !== 16) {
            throw new UnexpectedValueException(sprintf(
                'expects tag length to be 16, got instead %s',
                strlen($tag)
            ));
        }

        $this->tag = $tag;

        return $this;
    }

    /**
     * Get the Tag
     *
     * @return string
     *
     * @throws RuntimeException If the Tag is not set
     */
    public function getTag()
    {
        if (empty($this->tag)) {
            throw new RuntimeException('No tag set');
        }

        return $this->tag;
    }

    /**
     * Decrypt the given string
     *
     * @param string $data
     *
     * @return string
     *
     * @throws RuntimeException If decryption fails
     */
    public function decrypt($data)
    {
        if (! $this->isAuthenticatedEncryptionRequired()) {
            return $this->nonAEDecrypt($data);
        }

        $decrypt = openssl_decrypt($data, $this->getMethod(), $this->getKey(), 0, $this->getIV(), $this->getTag());

        if ($decrypt === false) {
            throw new RuntimeException('Decryption failed');
        }

        return $decrypt;
    }

    /**
     * Encrypt the given string
     *
     * @param string $data
     *
     * @return string encrypted string
     *
     * @throws RuntimeException If decryption fails
     */
    public function encrypt($data)
    {
        if (! $this->isAuthenticatedEncryptionRequired()) {
            return $this->nonAEEncrypt($data);
        }

        $encrypt = openssl_encrypt($data, $this->getMethod(), $this->getKey(), 0, $this->getIV(), $this->tag);

        if ($encrypt === false) {
            throw new RuntimeException('Encryption failed');
        }

        return $encrypt;
    }

    /**
     * Decrypt the given string with non Authenticated encryption (AE) cipher method
     *
     * @param string $data
     *
     * @return string decrypted string
     *
     * @throws RuntimeException If decryption fails
     */
    private function nonAEDecrypt($data)
    {
        $c = base64_decode($data);
        $hmac = substr($c, 0, 32);
        $data = substr($c, 32);

        $decrypt = openssl_decrypt($data, $this->getMethod(), $this->getKey(), 0, $this->getIV());
        $calcHmac = hash_hmac('sha256', $this->getIV() . $data, $this->getKey(), true);

        if ($decrypt === false || ! hash_equals($hmac, $calcHmac)) {
            throw new RuntimeException('Decryption failed');
        }

        return $decrypt;
    }

    /**
     * Encrypt the given string with non Authenticated encryption (AE) cipher method
     *
     * @param string $data
     *
     * @return string encrypted string
     *
     * @throws RuntimeException If encryption fails
     */
    private function nonAEEncrypt($data)
    {
        $encrypt = openssl_encrypt($data, $this->getMethod(), $this->getKey(), 0, $this->getIV());

        if ($encrypt === false) {
            throw new RuntimeException('Encryption failed');
        }

        $hmac = hash_hmac('sha256', $this->getIV() . $encrypt, $this->getKey(), true);

        return base64_encode($hmac . $encrypt);
    }

    /**
     * Whether the Authenticated encryption (a tag) is required
     *
     * @return bool True if required false otherwise
     */
    public function isAuthenticatedEncryptionRequired()
    {
        return $this->getMethod() === 'aes-256-gcm';
    }

    /**
     * Whether the php version supports Authenticated encryption (AE) or not
     *
     * @return bool True if supported false otherwise
     */
    public function isAuthenticatedEncryptionSupported()
    {
        return PHP_VERSION_ID >= 70100;
    }
}
