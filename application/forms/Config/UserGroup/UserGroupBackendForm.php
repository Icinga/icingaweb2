<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\UserGroup;

use Icinga\Authentication\UserGroup\UserGroupBackend;
use Icinga\Data\ConfigObject;
use Icinga\Data\Inspectable;
use Icinga\Data\Inspection;
use Icinga\Web\Form;
use InvalidArgumentException;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfigForm;

/**
 * Form for managing user group backends
 */
class UserGroupBackendForm extends ConfigForm
{
    protected $validatePartial = true;

    /**
     * The backend to load when displaying the form for the first time
     *
     * @var string
     */
    protected $backendToLoad;

    /**
     * Create a user group backend by using the given form's values and return its inspection results
     *
     * Returns null for non-inspectable backends.
     *
     * @param   Form    $form
     *
     * @return  Inspection|null
     */
    public static function inspectUserBackend(Form $form)
    {
        $backend = UserGroupBackend::create(null, new ConfigObject($form->getValues()));
        if ($backend instanceof Inspectable) {
            return $backend->inspect();
        }
    }

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_usergroupbackend');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * Return a form object for the given backend type
     *
     * @param   string  $type               The backend type for which to return a form
     *
     * @return  Form
     *
     * @throws  InvalidArgumentException    In case the given backend type is invalid
     */
    public function getBackendForm($type)
    {
        switch ($type) {
            case 'db':
                return new DbUserGroupBackendForm();
            case 'ldap':
            case 'msldap':
                return new LdapUserGroupBackendForm();
            default:
                throw new InvalidArgumentException(
                    sprintf($this->translate('Invalid backend type "%s" provided'), $type)
                );
        }
    }

    /**
     * Populate the form with the given backend's config
     *
     * @param   string  $name
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no backend with the given name is found
     */
    public function load($name)
    {
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No user group backend called "%s" found', $name);
        }

        $this->backendToLoad = $name;
        return $this;
    }

    /**
     * Add a new user group backend
     *
     * The backend to add is identified by the array-key `name'.
     *
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  InvalidArgumentException    In case $data does not contain a backend name
     * @throws  IcingaException             In case a backend with the same name already exists
     */
    public function add(array $data)
    {
        if (! isset($data['name'])) {
            throw new InvalidArgumentException('Key \'name\' missing');
        }

        $backendName = $data['name'];
        if ($this->config->hasSection($backendName)) {
            throw new IcingaException('A user group backend with the name "%s" does already exist', $backendName);
        }

        unset($data['name']);
        $this->config->setSection($backendName, $data);
        return $this;
    }

    /**
     * Edit a user group backend
     *
     * @param   string  $name
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no backend with the given name is found
     */
    public function edit($name, array $data)
    {
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No user group backend called "%s" found', $name);
        }

        $backendConfig = $this->config->getSection($name);
        if (isset($data['name'])) {
            if ($data['name'] !== $name) {
                $this->config->removeSection($name);
                $name = $data['name'];
            }

            unset($data['name']);
        }

        $this->config->setSection($name, $backendConfig->merge($data));
        return $this;
    }

    /**
     * Remove a user group backend
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function delete($name)
    {
        $this->config->removeSection($name);
        return $this;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData
     */
    public function createElements(array $formData)
    {
        // TODO(jom): We did not think about how to configure custom group backends yet!
        $backendTypes = array(
            'db'        => $this->translate('Database'),
            'ldap'      => 'LDAP',
            'msldap'    => 'ActiveDirectory'
        );

        $backendType = isset($formData['type']) ? $formData['type'] : null;
        if ($backendType === null) {
            $backendType = key($backendTypes);
        }

        $this->addElement(
            'select',
            'type',
            array(
                'ignore'            => true,
                'required'          => true,
                'autosubmit'        => true,
                'label'             => $this->translate('Backend Type'),
                'description'       => $this->translate('The type of this user group backend'),
                'multiOptions'      => $backendTypes
            )
        );

        $this->addSubForm($this->getBackendForm($backendType)->create($formData), 'backend_form');
    }

    /**
     * Populate the configuration of the backend to load
     */
    public function onRequest()
    {
        if ($this->backendToLoad) {
            $data = $this->config->getSection($this->backendToLoad)->toArray();
            $data['type'] = $data['backend'];
            $data['name'] = $this->backendToLoad;
            $this->populate($data);
        }
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
            $inspection = static::inspectUserBackend($this);
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
        }

        return true;
    }

    /**
     * Add a submit button to this form and one to manually validate the configuration
     *
     * Calls parent::addSubmitButton() to add the submit button.
     *
     * @return  $this
     */
    public function addSubmitButton()
    {
        parent::addSubmitButton()
            ->getElement('btn_submit')
            ->setDecorators(array('ViewHelper'));

        $this->addElement(
            'submit',
            'backend_validation',
            array(
                'ignore'                => true,
                'label'                 => $this->translate('Validate Configuration'),
                'data-progress-label'   => $this->translate('Validation In Progress'),
                'decorators'            => array('ViewHelper')
            )
        );
        $this->addDisplayGroup(
            array('btn_submit', 'backend_validation'),
            'submit_validation',
            array(
                'decorators' => array(
                    'FormElements',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                )
            )
        );

        return $this;
    }
}
