<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Tls\RootCaCollection;

use Exception;
use Icinga\Application\Hook;
use Icinga\File\Storage\LocalFileStorage;
use Icinga\Web\Form;

/**
 * Configuration form for editing TLS root CA certificate collections
 */
class EditForm extends Form
{
    /**
     * The TLS root CA certificate collection's old name
     *
     * @var string
     */
    protected $oldName;

    public function init()
    {
        $this->setName('form_config_tlsrootcacollection');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'label'         => $this->translate('Name'),
                'description'   => $this->translate('The TLS root CA certificate collection\'s name'),
                'required'      => true,
                'value'         => $this->oldName
            )
        );

        $this->addElement(
            'hidden',
            'old_name',
            array(
                'required'  => true,
                'disabled'  => true,
                'value'     => $this->oldName
            )
        );
    }

    public function onSuccess()
    {
        $name = $this->getElement('name')->getValue();

        if ($name !== $this->oldName) {
            /** @var Hook\TlsRootCACertificateCollectionHook[] $succeededCascades */
            $succeededCascades = array();

            foreach (Hook::all('TlsRootCACertificateCollection') as $hook) {
                /** @var Hook\TlsRootCACertificateCollectionHook $hook */

                try {
                    $hook->beforeRename($this->oldName, $name);
                } catch (Exception $e) {
                    foreach ($succeededCascades as $succeededCascade) {
                        try {
                            $succeededCascade->beforeRename($name, $this->oldName);
                        } catch (Exception $_) {
                        }
                    }

                    $this->error($e->getMessage());
                    return false;
                }

                $succeededCascades[] = $hook;
            }

            try {
                $rootCaCollections = LocalFileStorage::common('tls/rootcacollections');
                $oldFileName = bin2hex($this->oldName) . '.pem';

                $rootCaCollections->create(bin2hex($name) . '.pem', $rootCaCollections->read($oldFileName));
                $rootCaCollections->delete($oldFileName);
            } catch (Exception $e) {
                foreach ($succeededCascades as $succeededCascade) {
                    try {
                        $succeededCascade->beforeRename($name, $this->oldName);
                    } catch (Exception $_) {
                    }
                }

                $this->error($e->getMessage());
                return false;
            }
        }

        $this->getRedirectUrl()->setParam('name', $name);
    }

    /**
     * Set the TLS root CA certificate collection's old name
     *
     * @param string $oldName
     *
     * @return $this
     */
    public function setOldName($oldName)
    {
        $this->oldName = $oldName;

        return $this;
    }
}
