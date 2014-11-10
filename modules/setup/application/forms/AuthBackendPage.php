<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Form;

use Zend_Config;
use Icinga\Web\Form;
use Icinga\Web\Form\Element\Note;
use Icinga\Form\Config\Authentication\DbBackendForm;
use Icinga\Form\Config\Authentication\LdapBackendForm;
use Icinga\Form\Config\Authentication\AutologinBackendForm;

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
     * Return the resource configuration as Zend_Config object
     *
     * @return  Zend_Config
     */
    public function getResourceConfig()
    {
        return new Zend_Config($this->config);
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        if ($this->config['type'] === 'db') {
            $note = t(
                'As you\'ve chosen to use a database for authentication all you need '
                . 'to do now is defining a name for your first authentication backend.'
            );
        } elseif ($this->config['type'] === 'ldap') {
            $note = t(
                'Before you are able to authenticate using the LDAP connection defined earlier you need to'
                . ' provide some more information so that Icinga Web 2 is able to locate account details.'
            );
        } else { // if ($this->config['type'] === 'autologin'
            $note = t(
                'You\'ve chosen to authenticate using a web server\'s mechanism so it may be necessary'
                . ' to adjust usernames before any permissions, restrictions, etc. are being applied.'
            );
        }

        $this->addElement(
            new Note(
                'description',
                array('value' => $note)
            )
        );

        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            $this->addSkipValidationCheckbox();
        }

        if ($this->config['type'] === 'db') {
            $backendForm = new DbBackendForm();
            $backendForm->createElements($formData)->removeElement('resource');
        } elseif ($this->config['type'] === 'ldap') {
            $backendForm = new LdapBackendForm();
            $backendForm->createElements($formData)->removeElement('resource');
        } else { // $this->config['type'] === 'autologin'
            $backendForm = new AutologinBackendForm();
            $backendForm->createElements($formData);
        }

        $this->addElements($backendForm->getElements());
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
                'order'         => 1,
                'ignore'        => true,
                'required'      => true,
                'label'         => t('Skip Validation'),
                'description'   => t('Check this to not to validate authentication using this backend')
            )
        );
    }
}
