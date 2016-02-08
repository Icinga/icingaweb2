<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Exception;
use Icinga\Web\Form;
use Icinga\Forms\Config\Resource\DbResourceForm;
use Icinga\Module\Setup\Utils\DbTool;

/**
 * Wizard page to define connection details for a database resource
 */
class DbResourcePage extends Form
{
    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setTitle($this->translate('Database Resource', 'setup.page.title'));
        $this->setValidatePartial(true);
    }

    /**
     * @see Form::createElements()
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

        $resourceForm = new DbResourceForm();
        $this->addElements($resourceForm->createElements($formData)->getElements());
        $this->getElement('name')->setValue('icingaweb_db');
    }

    /**
     * Validate the given form data and check whether it's possible to connect to the database server
     *
     * @param   array   $data   The data to validate
     *
     * @return  bool
     */
    public function isValid($data)
    {
        if (false === parent::isValid($data)) {
            return false;
        }

        if (false === isset($data['skip_validation']) || $data['skip_validation'] == 0) {
            if (! $this->validateConfiguration()) {
                $this->addSkipValidationCheckbox();
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether it's possible to connect to the database server
     *
     * This will only run the check if the user pushed the 'backend_validation' button.
     *
     * @param   array   $formData
     *
     * @return  bool
     */
    public function isValidPartial(array $formData)
    {
        if (isset($formData['backend_validation']) && parent::isValid($formData)) {
            if (! $this->validateConfiguration()) {
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
     * @return  bool
     */
    protected function validateConfiguration()
    {
        try {
            $db = new DbTool($this->getValues());
            $db->checkConnectivity();
        } catch (Exception $e) {
            $this->error(sprintf(
                $this->translate('Failed to successfully validate the configuration: %s'),
                $e->getMessage()
            ));
            return false;
        }

        $state = true;
        $connectionError = null;

        try {
            $db->connectToDb();
        } catch (Exception $e) {
            $connectionError = $e;
        }

        if ($connectionError === null && array_search('icinga_instances', $db->listTables(), true) !== false) {
            $this->warning($this->translate(
                'The database you\'ve configured to use for Icinga Web 2 seems to be the one of Icinga. Please be aware'
                . ' that this database configuration is supposed to be used for Icinga Web 2\'s configuration and that'
                . ' it is highly recommended to not mix different schemas in the same database. If this is intentional,'
                . ' you can skip the validation and ignore this warning. If not, please provide a different database.'
            ));
            $state = false;
        }

        if ($this->getValue('db') === 'pgsql') {
            if ($connectionError !== null) {
                $this->warning($this->translate(sprintf(
                    'Unable to check the server\'s version. This is usually not a critical error as there is'
                    . ' probably only access to the database permitted which does not exist yet. If you are'
                    . ' absolutely sure you are running PostgreSQL in a version equal to or newer than 9.1,'
                    . ' you can skip the validation and safely proceed to the next step. The error was: %s',
                    $connectionError->getMessage()
                )));
                $state = false;
            } else {
                $version = $db->getServerVersion();
                if (version_compare($version, '9.1', '<')) {
                    $this->error($this->translate(sprintf(
                        'The server\'s version %s is too old. The minimum required version is %s.',
                        $version,
                        '9.1'
                    )));
                    $state = false;
                }
            }
        }

        return $state;
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
