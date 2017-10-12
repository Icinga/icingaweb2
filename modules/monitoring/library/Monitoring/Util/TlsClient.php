<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Util;

class TlsClient
{
    /**
     * Certificate
     *
     * @var string
     */
    protected $cert;

    /**
     * Private key
     *
     * @var string
     */
    protected $key;

    /**
     * Temporary directory with certificate and private key
     *
     * @var TemporaryDirectory|null
     */
    protected $certAndKeyDir;

    /**
     * Temporary file with certificate and private key
     *
     * @var string|null
     */
    protected $certAndKey;

    /**
     * Constructor
     *
     * @param   string  $cert   Certificate
     * @param   string  $key    Private key
     */
    public function __construct($cert, $key)
    {
        $this->cert = $cert;
        $this->key = $key;
    }

    /**
     * Get temporary path to certificate and private key
     *
     * @return string
     */
    public function getCertAndKey()
    {
        if ($this->certAndKey === null) {
            $this->certAndKeyDir = new TemporaryDirectory();
            $this->certAndKey = $this->certAndKeyDir . DIRECTORY_SEPARATOR . 'cert-and-key.pem';
            file_put_contents($this->certAndKey, $this->cert . PHP_EOL . $this->key);
        }

        return $this->certAndKey;
    }

    /**
     * Return certificate info as by {@link openssl_x509_parse()}
     *
     * @return array
     */
    public function getCertInfo()
    {
        return openssl_x509_parse($this->cert);
    }

    /**
     * Return certificate fingerprint as by {@link openssl_x509_fingerprint()}
     *
     * @param   string  $type
     * @param   bool    $binary
     *
     * @return  string
     */
    public function getCertFingerprint($type = 'sha1', $binary = false)
    {
        return openssl_x509_fingerprint($this->cert, $type, $binary);
    }
}
