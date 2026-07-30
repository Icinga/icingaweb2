<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Tls\RootCaCollection;

use Exception;
use Icinga\File\Storage\LocalFileStorage;
use Icinga\Web\Form;
use Icinga\Web\Form\Validator\TlsCertFileValidator;

/**
 * Configuration form for adding TLS root CA certificates
 */
class AddCaForm extends Form
{
    /**
     * The TLS root CA certificate collection's name
     *
     * @var string
     */
    protected $collectionName;

    public function init()
    {
        $this->setName('form_config_tlsrootca_add');
        $this->setSubmitLabel($this->translate('Add'));
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'file',
            'cert',
            array(
                'label'         => $this->translate('Certificate (PEM)'),
                'description'   => $this->translate('The new TLS root CA certificate'),
                'required'      => true,
                'validators'    => array(new TlsCertFileValidator())
            )
        );
    }

    public function onSuccess()
    {
        try {
            $rootCaCollections = LocalFileStorage::common('tls/rootcacollections');

            /** @var \Zend_Form_Element_File $cert */
            $cert = $this->getElement('cert');

            if ($cert->isUploaded()) {
                $cert->getValue();
            }

            openssl_x509_export('file://' . $cert->getFileName(), $newCert);

            $collectionFileName = bin2hex($this->collectionName) . '.pem';
            $rootCaCollections->update($collectionFileName, $newCert . $rootCaCollections->read($collectionFileName));
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
}
