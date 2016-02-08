<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Setup;

use Icinga\Data\ConfigObject;
use Icinga\Forms\Config\ResourceConfigForm;
use Icinga\Forms\Config\Resource\DbResourceForm;
use Icinga\Web\Form;
use Icinga\Module\Monitoring\Forms\Config\BackendConfigForm;
use Icinga\Module\Setup\Utils\DbTool;

class IdoResourcePage extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('setup_monitoring_ido');
        $this->setTitle($this->translate('Monitoring IDO Resource', 'setup.page.title'));
        $this->addDescription($this->translate(
            'Please fill out the connection details below to access the IDO database of your monitoring environment.'
        ));
        $this->setValidatePartial(true);
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData
     */
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
            // In case another error occured and the checkbox was displayed before
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

        $dbResourceForm = new DbResourceForm();
        $this->addElements($dbResourceForm->createElements($formData)->getElements());
        $this->getElement('name')->setValue('icinga_ido');
    }

    /**
     * Return whether the given values are valid
     *
     * @param   array   $formData   The data to validate
     *
     * @return  bool
     */
    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        if (! isset($formData['skip_validation']) || !$formData['skip_validation']) {
            if (! $this->validateConfiguration()) {
                $this->addSkipValidationCheckbox();
                return false;
            }
        }

        return true;
    }

    /**
     * Run the configured backend's inspection checks and show the result, if necessary
     *
     * This will only run any validation if the user pushed the 'backend_validation' button.
     *
     * @param   array   $formData
     *
     * @return  bool
     */
    public function isValidPartial(array $formData)
    {
        if (isset($formData['backend_validation']) && parent::isValid($formData)) {
            if (! $this->validateConfiguration(true)) {
                return false;
            }

            $this->info($this->translate('The configuration has been successfully validated.'));
        } elseif (! isset($formData['backend_validation'])) {
            // This is usually done by isValid(Partial), but as we're not calling any of these...
            $this->populate($formData);
        }

        return true;
    }

    /**
     * Return whether the configuration is valid
     *
     * @param   bool    $showLog    Whether to show the validation log
     *
     * @return  bool
     */
    protected function validateConfiguration($showLog = false)
    {
        $inspection = ResourceConfigForm::inspectResource($this);
        if ($inspection !== null) {
            if ($showLog) {
                $join = function ($e) use (& $join) {
                    return is_string($e) ? $e : join("\n", array_map($join, $e));
                };
                $this->addElement(
                    'note',
                    'inspection_output',
                    array(
                        'order'         => 0,
                        'value'         => '<strong>' . $this->translate('Validation Log') . "</strong>\n\n"
                            . join("\n", array_map($join, $inspection->toArray())),
                        'decorators'    => array(
                            'ViewHelper',
                            array('HtmlTag', array('tag' => 'pre', 'class' => 'log-output')),
                        )
                    )
                );
            }

            if ($inspection->hasError()) {
                $this->error(sprintf(
                    $this->translate('Failed to successfully validate the configuration: %s'),
                    $inspection->getError()
                ));
                return false;
            }
        }

        $configObject = new ConfigObject($this->getValues());
        if (
            ! BackendConfigForm::isValidIdoSchema($this, $configObject)
            || !BackendConfigForm::isValidIdoInstance($this, $configObject)
        ) {
            return false;
        }

        if ($this->getValue('db') === 'pgsql') {
            $db = new DbTool($this->getValues());
            $version = $db->connectToDb()->getServerVersion();
            if (version_compare($version, '9.1', '<')) {
                $this->error($this->translate(sprintf(
                    'The server\'s version %s is too old. The minimum required version is %s.',
                    $version,
                    '9.1'
                )));
                return false;
            }
        }

        return true;
    }

    /**
     * Add a checkbox to the form by which the user can skip the configuration validation
     */
    protected function addSkipValidationCheckbox()
    {
        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
                'required'      => true,
                'label'         => $this->translate('Skip Validation'),
                'description'   => $this->translate('Check this to not to validate the configuration')
            )
        );
    }
}
