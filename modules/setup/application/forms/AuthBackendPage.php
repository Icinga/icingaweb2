<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Icinga\Web\Form;
use Icinga\Forms\Config\Authentication\DbBackendForm;
use Icinga\Forms\Config\Authentication\LdapBackendForm;
use Icinga\Forms\Config\Authentication\ExternalBackendForm;
use Icinga\Data\ConfigObject;

/**
 * Wizard page to define authentication backend specific details
 */
class AuthBackendPage extends Form
{
    /**
     * The resource configuration to use
     *
     * @var array
     */
    protected $config;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_authentication_backend');
        $this->setTitle($this->translate('Authentication Backend', 'setup.page.title'));
    }

    /**
     * Set the resource configuration to use
     *
     * @param   array   $config
     *
     * @return  $this
     */
    public function setResourceConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Return the resource configuration as Config object
     *
     * @return  ConfigObject
     */
    public function getResourceConfig()
    {
        return new ConfigObject($this->config);
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            $this->addSkipValidationCheckbox();
        }

        if ($this->config['type'] === 'db') {
            $this->setRequiredCue(null);
            $backendForm = new DbBackendForm();
            $backendForm->setRequiredCue(null);
            $backendForm->createElements($formData)->removeElement('resource');
            $this->addDescription($this->translate(
                'As you\'ve chosen to use a database for authentication all you need '
                . 'to do now is defining a name for your first authentication backend.'
            ));
        } elseif ($this->config['type'] === 'ldap') {
            $backendForm = new LdapBackendForm();
            $backendForm->createElements($formData)->removeElement('resource');
            $this->addDescription($this->translate(
                'Before you are able to authenticate using the LDAP connection defined earlier you need to'
                . ' provide some more information so that Icinga Web 2 is able to locate account details.'
            ));
        } else { // $this->config['type'] === 'external'
            $backendForm = new ExternalBackendForm();
            $backendForm->createElements($formData);
            $this->addDescription($this->translate(
                'You\'ve chosen to authenticate using a web server\'s mechanism so it may be necessary'
                . ' to adjust usernames before any permissions, restrictions, etc. are being applied.'
            ));
        }

        $this->addElements($backendForm->getElements());
        $this->getElement('name')->setValue('icingaweb2');
    }

    /**
     * Validate the given form data and check whether it's possible to authenticate using the configured backend
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
            if ($this->config['type'] === 'ldap' && false === LdapBackendForm::isValidAuthenticationBackend($this)) {
                $this->addSkipValidationCheckbox();
                return false;
            }
        }

        return true;
    }

    /**
     * Add a checkbox to this form by which the user can skip the authentication validation
     */
    protected function addSkipValidationCheckbox()
    {
        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
                'order'         => 0,
                'ignore'        => true,
                'required'      => true,
                'label'         => $this->translate('Skip Validation'),
                'description'   => $this->translate('Check this to not to validate authentication using this backend')
            )
        );
    }
}
