<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config;

use InvalidArgumentException;
use Icinga\Application\Config;
use Icinga\Authentication\User\UserBackend;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotFoundError;
use Icinga\Data\ConfigObject;
use Icinga\Data\Inspectable;
use Icinga\Data\Inspection;
use Icinga\Forms\ConfigForm;
use Icinga\Forms\Config\UserBackend\ExternalBackendForm;
use Icinga\Forms\Config\UserBackend\DbBackendForm;
use Icinga\Forms\Config\UserBackend\LdapBackendForm;
use Icinga\Web\Form;

/**
 * Form for managing user backends
 */
class UserBackendConfigForm extends ConfigForm
{
    /**
     * The available user backend resources split by type
     *
     * @var array
     */
    protected $resources;

    /**
     * The backend to load when displaying the form for the first time
     *
     * @var string
     */
    protected $backendToLoad;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_authbackend');
        $this->setSubmitLabel($this->translate('Save Changes'));
        $this->setValidatePartial(true);
    }

    /**
     * Set the resource configuration to use
     *
     * @param   Config  $resourceConfig     The resource configuration
     *
     * @return  $this
     *
     * @throws  ConfigurationError          In case there are no valid resources for authentication available
     */
    public function setResourceConfig(Config $resourceConfig)
    {
        $resources = array();
        foreach ($resourceConfig as $name => $resource) {
            if (in_array($resource->type, array('db', 'ldap'))) {
                $resources[$resource->type][] = $name;
            }
        }

        if (empty($resources)) {
            $externalBackends = $this->config->toArray();
            array_walk(
                $externalBackends,
                function (& $authBackendCfg) {
                    if (! isset($authBackendCfg['backend']) || $authBackendCfg['backend'] !== 'external') {
                        $authBackendCfg = null;
                    }
                }
            );
            if (count(array_filter($externalBackends)) > 0 && (
                $this->backendToLoad === null || !isset($externalBackends[$this->backendToLoad])
            )) {
                throw new ConfigurationError($this->translate(
                    'Could not find any valid user backend resources.'
                    . ' Please configure a resource for authentication first.'
                ));
            }
        }

        $this->resources = $resources;
        return $this;
    }

    /**
     * Return a form object for the given backend type
     *
     * @param   string      $type           The backend type for which to return a form
     *
     * @return  Form
     *
     * @throws  InvalidArgumentException    In case the given backend type is invalid
     */
    public function getBackendForm($type)
    {
        switch ($type) {
            case 'db':
                $form = new DbBackendForm();
                $form->setResources(isset($this->resources['db']) ? $this->resources['db'] : array());
                break;
            case 'ldap':
            case 'msldap':
                $form = new LdapBackendForm();
                $form->setResources(isset($this->resources['ldap']) ? $this->resources['ldap'] : array());
                break;
            case 'external':
                $form = new ExternalBackendForm();
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf($this->translate('Invalid backend type "%s" provided'), $type)
                );
        }

        return $form;
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
            throw new NotFoundError('No user backend called "%s" found', $name);
        }

        $this->backendToLoad = $name;
        return $this;
    }

    /**
     * Add a new user backend
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
            throw new IcingaException(
                $this->translate('A user backend with the name "%s" does already exist'),
                $backendName
            );
        }

        unset($data['name']);
        $this->config->setSection($backendName, $data);
        return $this;
    }

    /**
     * Edit a user backend
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
            throw new NotFoundError('No user backend called "%s" found', $name);
        }

        $backendConfig = $this->config->getSection($name);
        if (isset($data['name'])) {
            if ($data['name'] !== $name) {
                $this->config->removeSection($name);
                $name = $data['name'];
            }

            unset($data['name']);
        }

        $backendConfig->merge($data);
        $this->config->setSection($name, $backendConfig);
        return $this;
    }

    /**
     * Remove a user backend
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
     * Move the given user backend up or down in order
     *
     * @param   string      $name           The name of the backend to be moved
     * @param   int         $position       The new (absolute) position of the backend
     *
     * @return  $this
     *
     * @throws  NotFoundError               In case no backend with the given name is found
     */
    public function move($name, $position)
    {
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No user backend called "%s" found', $name);
        }

        $backendOrder = $this->config->keys();
        array_splice($backendOrder, array_search($name, $backendOrder), 1);
        array_splice($backendOrder, $position, 0, $name);

        $newConfig = array();
        foreach ($backendOrder as $backendName) {
            $newConfig[$backendName] = $this->config->getSection($backendName);
        }

        $config = Config::fromArray($newConfig);
        $this->config = $config->setConfigFile($this->config->getConfigFile());
        return $this;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData
     */
    public function createElements(array $formData)
    {
        $backendTypes = array();
        $backendType = isset($formData['type']) ? $formData['type'] : null;

        if (isset($this->resources['db'])) {
            $backendTypes['db'] = $this->translate('Database');
        }
        if (isset($this->resources['ldap'])) {
            $backendTypes['ldap'] = 'LDAP';
            $backendTypes['msldap'] = 'ActiveDirectory';
        }

        $externalBackends = array_filter(
            $this->config->toArray(),
            function ($authBackendCfg) {
                return isset($authBackendCfg['backend']) && $authBackendCfg['backend'] === 'external';
            }
        );
        if ($backendType === 'external' || empty($externalBackends)) {
            $backendTypes['external'] = $this->translate('External');
        }

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
                'description'       => $this->translate(
                    'The type of the resource to use for this authenticaton provider'
                ),
                'multiOptions'      => $backendTypes
            )
        );

        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            // In case another error occured and the checkbox was displayed before
            $this->addSkipValidationCheckbox();
        }

        $this->addSubForm($this->getBackendForm($backendType)->create($formData), 'backend_form');
    }

    /**
     * Populate the configuration of the backend to load
     */
    public function onRequest()
    {
        if ($this->backendToLoad) {
            $data = $this->config->getSection($this->backendToLoad)->toArray();
            $data['name'] = $this->backendToLoad;
            $data['type'] = $data['backend'];
            $this->populate($data);
        }
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

        if (($el = $this->getElement('skip_validation')) === null || false === $el->isChecked()) {
            $inspection = static::inspectUserBackend($this);
            if ($inspection && $inspection->hasError()) {
                $this->error($inspection->getError());
                if ($el === null) {
                    $this->addSkipValidationCheckbox();
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Create a user backend by using the given form's values and return its inspection results
     *
     * Returns null for non-inspectable backends.
     *
     * @param   Form    $form
     *
     * @return  Inspection|null
     */
    public static function inspectUserBackend(Form $form)
    {
        $backend = UserBackend::create(null, new ConfigObject($form->getValues()));
        if ($backend instanceof Inspectable) {
            return $backend->inspect();
        }
    }

    /**
     * Add a checkbox to the form by which the user can skip the connection validation
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
                'description'   => $this->translate(
                    'Check this box to enforce changes without validating that authentication is possible.'
                )
            )
        );
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
        if (! parent::isValidPartial($formData)) {
            return false;
        }

        if ($this->getElement('backend_validation')->isChecked() && parent::isValid($formData)) {
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
