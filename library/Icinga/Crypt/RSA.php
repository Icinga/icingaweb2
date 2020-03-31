<?php
/* Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+ */

namespace Icinga\Crypt;

use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Data encryption and decryption using RSA keys also supporting key generation
 *
 * # Example Usage
 *
 * ```php
 * // Key generation
 * list($privateKey, $publicKey) = RSA::keygen();
 *
 * // Encryption
 * $data = (new RSA())
 *     ->loadKey($publicKey)
 *     ->encrypt($a, $b, $c); // Accepts a variable number of arguments
 *
 * // Encrypt and encode to Base64
 * $data = (new RSA())
 *     ->loadKey($publicKey)
 *     ->encryptToBase64($a, $b, $c); // Accepts a variable number of arguments
 *
 *
 * // Decryption
 * list($a, $b, $c) = (new RSA())
 *     ->loadKey($privateKey)
 *     ->decrypt(...$data);
 *
 * // Decode from Base64 and decrypt
 * list($a, $b, $c) = (new RSA())
 *     ->loadKey($privateKey)
 *     ->decryptFromBase64(...$data);
 * ```
 *
 */
class RSA
{
    /** @var string */
    private $pubKey;

    /** @var string */
    private $privKey;

    /**
     * Generate a new private and public key pair
     *
     * @return array
     */
    public static function keygen()
    {
        $res = openssl_pkey_new([
            'digest_alg'       => 'sha512',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($res, $privKey);

        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey['key'];

        return [$privKey, $pubKey];
    }

    /**
     * Get the public key
     *
     * @return string
     *
     * @throws UnexpectedValueException If the public key is not set
     */
    public function getPublicKey()
    {
        if (empty($this->pubKey)) {
            throw new UnexpectedValueException('No public key set');
        }

        return $this->pubKey;
    }

    /**
     * Get the private key
     *
     * @return string
     *
     * @throws UnexpectedValueException If the private key is not set
     */
    public function getPrivateKey()
    {
        if (empty($this->privKey)) {
            throw new UnexpectedValueException('No private key set');
        }

        return $this->privKey;
    }

    /**
     * Decrypt the given data
     *
     * @param mixed ...$data
     *
     * @return array
     *
     * @throws UnexpectedValueException If the private key is not set
     */
    public function decrypt(...$data)
    {
        $decryptedValues = array();
        foreach ($data as $valueToDecrypt) {
            openssl_private_decrypt($valueToDecrypt, $decryptedValues[], $this->getPrivateKey());
        }

        return $decryptedValues;
    }

    /**
     * Decrypt and decodes the given data which is encoded in MIME base64
     *
     * @param mixed ...$data
     *
     * @return array
     *
     * @throws UnexpectedValueException If the private key is not set
     */
    public function decryptFromBase64(...$data)
    {
        $decodedValues = array();
        foreach ($data as $decodeValue) {
            $decodedValues[] = base64_decode($decodeValue);
        }
        $decryptedData = $this->decrypt(...$decodedValues);

        return $decryptedData;
    }

    /**
     * Encrypt the given data
     *
     * @param mixed ...$data
     *
     * @return array
     *
     * @throws UnexpectedValueException If the public key is not set
     */
    public function encrypt(...$data)
    {
        $encryptedValues = array();
        foreach ($data as $valueTOEncrypt) {
            openssl_public_encrypt($valueTOEncrypt, $encryptedValues[], $this->getPublicKey());
        }

        return $encryptedValues;
    }

    /**
     * Encrypt and encodes the given data with MIME base64
     *
     * @param mixed ...$data
     *
     * @return array
     *
     * @throws UnexpectedValueException If the public key is not set
     */
    public function encryptToBase64(...$data)
    {
        $data = $this->encrypt(...$data);
        $encodedValues = array();
        foreach ($data as $valueToEncode) {
            $encodedValues[] = base64_encode($valueToEncode);
        }

        return $encodedValues;
    }

    /**
     * Save the given values in the Variables (private and public key)
     *
     * @param string ...$key
     *
     * @return $this
     *
     * @throws InvalidArgumentException If more than two keys are passed to the function
     */
    public function loadKey(...$key)
    {
        if (count($key) > 2) {
            throw new InvalidArgumentException(sprintf(
                '%s expects at most 2 keys, %d given',
                __METHOD__,
                count($key)
            ));
        }
        foreach ($key as $k) {
            if (strpos($k, 'PUBLIC')) {
                $this->pubKey = $k;
            } else {
                $this->privKey = $k;
            }
        }

        return  $this;
    }
}
