<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Tls\RootCaCollection;

use Exception;
use Icinga\File\Storage\LocalFileStorage;
use Icinga\Web\Form;

/**
 * Configuration form for creating TLS root CA certificate collections
 */
class CreateForm extends Form
{
    public function init()
    {
        $this->setName('form_config_tlsrootcacollection');
        $this->setSubmitLabel($this->translate('Create'));
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'label'         => $this->translate('Name'),
                'description'   => $this->translate('The new TLS root CA certificate collection\'s name'),
                'required'      => true
            )
        );
    }

    public function onSuccess()
    {
        $name = $this->getElement('name')->getValue();

        try {
            LocalFileStorage::common('tls/rootcacollections')->create(bin2hex($name) . '.pem', '');
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return false;
        }

        $this->getRedirectUrl()->setParam('name', $name);
    }
}
