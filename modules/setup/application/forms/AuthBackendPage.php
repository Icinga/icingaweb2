<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Icinga\Application\Config;
use Icinga\Data\ResourceFactory;
use Icinga\Forms\Config\UserBackendConfigForm;
use Icinga\Forms\Config\UserBackend\DbBackendForm;
use Icinga\Forms\Config\UserBackend\LdapBackendForm;
use Icinga\Forms\Config\UserBackend\ExternalBackendForm;
use Icinga\Web\Form;

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
        $this->setValidatePartial(true);
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
        $resourceConfig = new Config();
        $resourceConfig->setSection($config['name'], $config);
        ResourceFactory::setConfig($resourceConfig);

        $this->config = $config;
        return $this;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData
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
            $backendForm->create($formData)->removeElement('resource');
            $this->addDescription($this->translate(
                'As you\'ve chosen to use a database for authentication all you need '
                . 'to do now is defining a name for your first authentication backend.'
            ));
        } elseif ($this->config['type'] === 'ldap') {
            $type = null;
            if (! isset($formData['type']) && isset($formData['backend'])) {
                $type = $formData['backend'];
                $formData['type'] = $type;
            }

            $backendForm = new LdapBackendForm();
            $backendForm->setResources(array($this->config['name']));
            $backendForm->create($formData);
            $backendForm->getElement('resource')->setIgnore(true);
            $this->addDescription($this->translate(
                'Before you are able to authenticate using the LDAP connection defined earlier you need to'
                . ' provide some more information so that Icinga Web 2 is able to locate account details.'
            ));
            $this->addElement(
                'select',
                'type',
                array(
                    'ignore'            => true,
                    'required'          => true,
                    'autosubmit'        => true,
                    'label'             => $this->translate('Backend Type'),
                    'description'       => $this->translate(
                        'The type of the resource being used for this authenticaton provider'
                    ),
                    'multiOptions'      => array(
                        'ldap'      => 'LDAP',
                        'msldap'    => 'ActiveDirectory'
                    ),
                    'value'             => $type
                )
            );
        } else { // $this->config['type'] === 'external'
            $backendForm = new ExternalBackendForm();
            $backendForm->create($formData);
            $this->addDescription($this->translate(
                'You\'ve chosen to authenticate using a web server\'s mechanism so it may be necessary'
                . ' to adjust usernames before any permissions, restrictions, etc. are being applied.'
            ));
        }

        $backendForm->getElement('name')->setValue('icingaweb2');
        $this->addSubForm($backendForm, 'backend_form');
    }

    /**
     * Retrieve all form element values
     *
     * @param   bool    $suppressArrayNotation  Ignored
     *
     * @return  array
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues();
        $values = array_merge($values, $values['backend_form']);
        unset($values['backend_form']);
        return $values;
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
        if (! parent::isValid($data)) {
            return false;
        }

        if ($this->config['type'] === 'ldap' && (! isset($data['skip_validation']) || $data['skip_validation'] == 0)) {
            $self = clone $this;
            $self->getSubForm('backend_form')->getElement('resource')->setIgnore(false);
            $inspection = UserBackendConfigForm::inspectUserBackend($self);
            if ($inspection && $inspection->hasError()) {
                $this->error($inspection->getError());
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
            $self = clone $this;
            if (($resourceElement = $self->getSubForm('backend_form')->getElement('resource')) !== null) {
                $resourceElement->setIgnore(false);
            }

            $inspection = UserBackendConfigForm::inspectUserBackend($self);
            if ($inspection !== null) {
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

                if ($inspection->hasError()) {
                    $this->warning(sprintf(
                        $this->translate('Failed to successfully validate the configuration: %s'),
                        $inspection->getError()
                    ));
                    return false;
                }
            }

            $this->info($this->translate('The configuration has been successfully validated.'));
        } elseif (! isset($formData['backend_validation'])) {
            // This is usually done by isValid(Partial), but as we're not calling any of these...
            $this->populate($formData);
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
