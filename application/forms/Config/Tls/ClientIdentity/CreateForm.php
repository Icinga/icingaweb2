<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Tls\ClientIdentity;

use Exception;
use Icinga\File\Storage\LocalFileStorage;
use Icinga\Web\Form;
use Icinga\Web\Form\Validator\TlsCertFileValidator;
use Icinga\Web\Form\Validator\TlsKeyFileValidator;

/**
 * Configuration form for creating TLS client identities
 */
class CreateForm extends Form
{
    public function init()
    {
        $this->setName('form_config_tlsclientidentity');
        $this->setSubmitLabel($this->translate('Create'));
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'label'         => $this->translate('Name'),
                'description'   => $this->translate('The new TLS client identity\'s name'),
                'required'      => true
            )
        );

        $this->addElement(
            'file',
            'cert',
            array(
                'label'         => $this->translate('Certificate (PEM)'),
                'description'   => $this->translate('The new TLS client certificate'),
                'required'      => true,
                'validators'    => array(new TlsCertFileValidator())
            )
        );

        $this->addElement(
            'file',
            'key',
            array(
                'label'         => $this->translate('Private Key (PEM)'),
                'description'   => $this->translate('The new TLS client private key'),
                'required'      => true,
                'validators'    => array(new TlsKeyFileValidator())
            )
        );
    }

    public function onSuccess()
    {
        $name = $this->getElement('name')->getValue();

        try {
            /** @var \Zend_Form_Element_File $cert */
            $cert = $this->getElement('cert');

            if ($cert->isUploaded()) {
                $cert->getValue();
            }

            openssl_x509_export('file://' . $cert->getFileName(), $newCert);

            /** @var \Zend_Form_Element_File $key */
            $key = $this->getElement('key');

            if ($key->isUploaded()) {
                $key->getValue();
            }

            openssl_pkey_export('file://' . $key->getFileName(), $newKey);

            $newCertDetails = openssl_pkey_get_details(openssl_pkey_get_public($newCert));
            $newKeyDetails = openssl_pkey_get_details(openssl_pkey_get_private($newKey));

            if ($newCertDetails['key'] !== $newKeyDetails['key']) {
                $this->error($this->translate('The public keys of the certificate and the private key don\'t match'));
                return false;
            }

            LocalFileStorage::common('tls/clientidentities')->create(bin2hex($name) . '.pem', $newCert . $newKey);
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return false;
        }

        $this->getRedirectUrl()->setParam('name', $name);
    }
}
