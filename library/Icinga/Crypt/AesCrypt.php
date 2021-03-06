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
 * $encryptedData = new AesCrypt()->encrypt($data); // Accepts a string
 *
 *
 * // Encrypt and encode to Base64
 * $encryptedData = (new AesCrypt())->encryptToBase64($data); // Accepts a string
 *
 *
 * // Decryption
 * $aesCrypt = (new AesCrypt())
 *          ->setTag($tag)
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
 * $decryptedData = $aesCrypt->->decryptFromBase64($data);
 * ```
 *
 */
class AesCrypt
{
    /** @var string The encryption key */
    private $key;

    /** @var string The initialization vector which is not NULL */
    private $iv;

    /** @var string The authentication tag which is passed by reference when using AEAD cipher mode */
    private $tag;

    /** @var string The cipher method */
    private $method = 'aes-128-gcm';

    public function __construct($random_bytes_len = 128)
    {
        $len = openssl_cipher_iv_length($this->method);
        $this->iv = random_bytes($len);
        $this->key = random_bytes($random_bytes_len);
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
     * @throws RuntimeException If the key is not set
     */
    public function getKey()
    {
        if (empty($this->key)) {
            throw new RuntimeException('No key set');
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
     * @throws RuntimeException If the IV is not set
     */
    public function getIV()
    {
        if (empty($this->iv)) {
            throw new RuntimeException('No iv set');
        }

        return $this->iv;
    }

    /**
     * Set the Tag
     *
     * @return $this
     */
    public function setTag($tag)
    {
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
     * Decrypt the given data using the key, iv and tag
     *
     * @param string $data
     *
     * @return string
     *
     * @throws RuntimeException If decryption fails
     */
    public function decrypt($data)
    {
        $decrypt = openssl_decrypt($data, $this->method, $this->getKey(), 0, $this->getIV(), $this->getTag());
        if (is_bool($decrypt) && $decrypt === false) {
            throw new RuntimeException('Decryption failed');
        }

        return $decrypt;
    }

    /**
     * Decode from Base64 and decrypt the given data using the key, iv and tag
     *
     * @param string $data
     *
     * @return string decrypted data
     *
     * @throws RuntimeException If decryption fails
     */
    public function decryptFromBase64($data)
    {
        return $this->decrypt(base64_decode($data));
    }

    /**
     * Encrypt the given data using the key, iv and tag
     *
     * @param string $data
     *
     * @return string encrypted data
     *
     * @throws RuntimeException If decryption fails
     */
    public function encrypt($data)
    {
        $encrypt = openssl_encrypt($data, $this->method, $this->getkey(), 0, $this->getIV(), $this->tag);

        if (is_bool($encrypt) && $encrypt === false) {
            throw new RuntimeException('Encryption failed');
        }

        return $encrypt;
    }

    /**
     * Encrypt the given string using the the key, iv, tag and encode to Base64
     *
     * @param string $data
     *
     * @return string encrypted and encoded to Base64 data
     *
     * @throws RuntimeException If encryption fails
     */
    public function encryptToBase64($data)
    {
        return base64_encode($this->encrypt($data));
    }
}
