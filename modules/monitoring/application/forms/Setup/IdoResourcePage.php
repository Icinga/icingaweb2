<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Setup;

use Icinga\Data\ConfigObject;
use Icinga\Module\Monitoring\Forms\Config\BackendConfigForm;
use Icinga\Web\Form;
use Icinga\Forms\Config\Resource\DbResourceForm;

class IdoResourcePage extends Form
{
    public function init()
    {
        $this->setName('setup_monitoring_ido');
        $this->setTitle($this->translate('Monitoring IDO Resource', 'setup.page.title'));
        $this->addDescription($this->translate(
            'Please fill out the connection details below to access the IDO database of your monitoring environment.'
        ));
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'hidden',
            'type',
            array(
                'required'  => true,
                'value'     => 'db'
            )
        );

        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            $this->addSkipValidationCheckbox();
        } else {
            $this->addElement(
                'hidden',
                'skip_validation',
                array(
                    'required'  => true,
                    'value'     => 0
                )
            );
        }

        $livestatusResourceForm = new DbResourceForm();
        $this->addElements($livestatusResourceForm->createElements($formData)->getElements());
        $this->getElement('name')->setValue('icinga_ido');
    }

    public function isValid($data)
    {
        if (false === parent::isValid($data)) {
            return false;
        }

        if (false === isset($data['skip_validation']) || $data['skip_validation'] == 0) {
            $configObject = new ConfigObject($this->getValues());
            if (false === DbResourceForm::isValidResource($this, $configObject)) {
                $this->addSkipValidationCheckbox($this->translate(
                    'Check this to not to validate connectivity with the given database server'
                ));
                return false;
            } elseif (false === BackendConfigForm::isValidIdoSchema($this, $configObject)) {
                $this->addSkipValidationCheckbox($this->translate(
                    'Check this to not to validate the ido schema'
                ));
                return false;
            } elseif (false === BackendConfigForm::isValidIdoInstance($this, $configObject)) {
                $this->addSkipValidationCheckbox($this->translate(
                    'Check this to not to validate the ido instance'
                ));
                return false;
            }
        }

        return true;
    }

    /**
     * Add a checkbox to the form by which the user can skip the connection validation
     */
    protected function addSkipValidationCheckbox($description = '')
    {
        if (empty($description)) {
            $description = $this->translate(
                'Proceed without any further (custom) validation'
            );
        }

        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
                'required'      => true,
                'label'         => $this->translate('Skip Validation'),
                'description'   => $description
            )
        );
    }
}
