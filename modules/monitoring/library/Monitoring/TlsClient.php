<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring;

use ErrorException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

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
     * Temporary path to certificate and private key
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
            $certAndKey = $this->tempdir() . DIRECTORY_SEPARATOR . 'cert-and-key.pem';
            file_put_contents($certAndKey, $this->cert . PHP_EOL . $this->key);
            $this->certAndKey = $certAndKey;
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

    /**
     * Create a temporary directory and return its path
     *
     * @return string
     */
    protected function tempdir()
    {
        $tempRootDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR;

        for (;;) {
            $tempDir = $tempRootDir . uniqid();
            try {
                mkdir($tempDir, 0700);
            } catch (ErrorException $e) {
                continue;
            }

            register_shutdown_function(function() use($tempDir) {
                foreach (new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $tempDir,
                        RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
                        | RecursiveDirectoryIterator::KEY_AS_PATHNAME
                        | RecursiveDirectoryIterator::SKIP_DOTS
                    ),
                    RecursiveIteratorIterator::CHILD_FIRST
                ) as $path => $entry) {
                    /** @var SplFileInfo $entry */

                    if ($entry->isDir()) {
                        rmdir($path);
                    } else {
                        unlink($path);
                    }
                }

                rmdir($tempDir);
            });

            return $tempDir;
        }
    }
}
