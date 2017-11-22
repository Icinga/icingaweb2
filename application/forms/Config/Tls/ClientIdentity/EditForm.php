<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Tls\ClientIdentity;

use Exception;
use Icinga\Application\Hook;
use Icinga\File\Storage\LocalFileStorage;
use Icinga\Web\Form;

/**
 * Configuration form for editing TLS client identities
 */
class EditForm extends Form
{
    /**
     * The TLS client identity's old name
     *
     * @var string
     */
    protected $oldName;

    public function init()
    {
        $this->setName('form_config_tlsclientidentity');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'label'         => $this->translate('Name'),
                'description'   => $this->translate('The TLS client identity\'s name'),
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
            /** @var Hook\TlsClientIdentityHook[] $succeededCascades */
            $succeededCascades = array();

            foreach (Hook::all('TlsClientIdentity') as $hook) {
                /** @var Hook\TlsClientIdentityHook $hook */

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
                $clientIdentities = LocalFileStorage::common('tls/clientidentities');
                $oldFileName = bin2hex($this->oldName) . '.pem';

                $clientIdentities->create(bin2hex($name) . '.pem', $clientIdentities->read($oldFileName));
                $clientIdentities->delete($oldFileName);
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
     * Set the TLS client identity's old name
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
