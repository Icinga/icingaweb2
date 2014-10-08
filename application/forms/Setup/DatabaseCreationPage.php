<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Setup;

use PDOException;
use Icinga\Web\Form;
use Icinga\Web\Form\Element\Note;
use Icinga\Web\Setup\DbTool;

/**
 * Wizard page to define a database user that is able to create databases and tables
 */
class DatabaseCreationPage extends Form
{
    /**
     * The resource configuration to use
     *
     * @var array
     */
    protected $config;

    /**
     * The required database privileges
     *
     * @var array
     */
    protected $databasePrivileges;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_database_creation');
    }

    /**
     * Set the resource configuration to use
     *
     * @param   array   $config
     *
     * @return  self
     */
    public function setResourceConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Set the required database privileges
     *
     * @param   array   $privileges     The required privileges
     *
     * @return  self
     */
    public function setDatabasePrivileges(array $privileges)
    {
        $this->databasePrivileges = $privileges;
        return $this;
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            new Note(
                'description',
                array(
                    'value' => t(
                        'It seems that either the database you defined earlier does not yet exist and cannot be created'
                        . ' using the provided access credentials or the database does not have the required schema to '
                        . 'be operated by Icinga Web 2. Please provide appropriate access credentials to solve this.'
                    )
                )
            )
        );

        $skipValidation = isset($formData['skip_validation']) && $formData['skip_validation'];
        $this->addElement(
            'text',
            'username',
            array(
                'required'      => false === $skipValidation,
                'label'         => t('Username'),
                'description'   => t('A user which is able to create databases and/or touch the database schema')
            )
        );
        $this->addElement(
            'password',
            'password',
            array(
                'required'      => false === $skipValidation,
                'label'         => t('Password'),
                'description'   => t('The password for the database user defined above')
            )
        );

        if ($skipValidation) {
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
    }

    /**
     * Validate the given form data and check whether the defined user has sufficient access rights
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

        if (isset($data['skip_validation']) && $data['skip_validation']) {
            return true;
        }

        $this->config['username'] = $this->getValue('username');
        $this->config['password'] = $this->getValue('password');
        $db = new DbTool($this->config);

        try {
            $db->connectToDb();
            if (false === $db->checkPrivileges($this->databasePrivileges)) {
                $this->addError(
                    t('The provided credentials do not have the required access rights to create the database schema.')
                );
                $this->addSkipValidationCheckbox();
                return false;
            }
        } catch (PDOException $e) {
            try {
                $db->connectToHost();
                if (false === $db->checkPrivileges($this->databasePrivileges)) {
                    $this->addError(
                        t('The provided credentials cannot be used to create the database and/or the user.')
                    );
                    $this->addSkipValidationCheckbox();
                    return false;
                }
            } catch (PDOException $e) {
                $this->addError($e->getMessage());
                $this->addSkipValidationCheckbox();
                return false;
            }
        }

        return true;
    }

    /**
     * Add a checkbox to the form by which the user can skip the login and privilege validation
     */
    protected function addSkipValidationCheckbox()
    {
        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
                'order'         => 1,
                'required'      => true,
                'label'         => t('Skip Validation'),
                'description'   => t('Check this to not to validate the ability to login and required privileges')
            )
        );
    }
}
