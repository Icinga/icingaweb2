<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Setup;

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
        $this->addElement(
            new Note(
                'description',
                array(
                    'value' => sprintf(
                        t(
                            'Now please enter all configuration details required'
                            . ' to authenticate using this %s backend.',
                            'setup.auth.backend'
                        ),
                        $this->config['type'] === 'db' ? t('database', 'setup.auth.backend.type') : (
                            $this->config['type'] === 'ldap' ? 'LDAP' : t('autologin', 'setup.auth.backend.type')
                        )
                    )
                )
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
