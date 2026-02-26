<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Tls\RootCaCollection;

use Exception;
use Icinga\File\Storage\LocalFileStorage;
use Icinga\Web\Form;
use Icinga\Web\Form\Validator\TlsCertFileValidator;

/**
 * Configuration form for removing TLS root CA certificates
 */
class RemoveCaForm extends Form
{
    /**
     * The TLS root CA certificate collection's name
     *
     * @var string
     */
    protected $collectionName;

    /**
     * The TLS root CA certificate's SHA256 sum
     *
     * @var string
     */
    protected $certBySha256;

    public function init()
    {
        $this->setName('form_config_tlsrootca_remove_' . $this->certBySha256);
        $this->setSubmitLabel($this->translate('Remove'));
    }

    public function onSuccess()
    {
        try {
            $rootCaCollections = LocalFileStorage::common('tls/rootcacollections');
            $collectionFileName = bin2hex($this->collectionName) . '.pem';

            preg_match_all(
                '/-+BEGIN CERTIFICATE-+.+?-+END CERTIFICATE-+/s',
                $rootCaCollections->read($collectionFileName),
                $certs
            );

            $certs = $certs[0];

            foreach ($certs as $index => $cert) {
                if (openssl_x509_fingerprint($cert, 'sha256') === $this->certBySha256) {
                    unset($certs[$index]);
                    $rootCaCollections->update($collectionFileName, implode(PHP_EOL, $certs));

                    break;
                }
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return false;
        }

        $this->getRedirectUrl()->setParam('name', $this->collectionName);
    }

    /**
     * Set the TLS root CA certificate collection's name
     *
     * @param string $collectionName
     *
     * @return $this
     */
    public function setCollectionName($collectionName)
    {
        $this->collectionName = $collectionName;

        return $this;
    }

    /**
     * Set the TLS root CA certificate's SHA256 sum
     *
     * @param string $certBySha256
     *
     * @return $this
     */
    public function setCertBySha256($certBySha256)
    {
        $this->certBySha256 = $certBySha256;

        return $this;
    }
}
