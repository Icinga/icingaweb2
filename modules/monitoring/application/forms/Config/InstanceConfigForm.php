<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config;

use InvalidArgumentException;
use Icinga\Web\Request;
use Icinga\Form\ConfigForm;
use Icinga\Web\Notification;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Monitoring\Form\Config\Instance\LocalInstanceForm;
use Icinga\Module\Monitoring\Form\Config\Instance\RemoteInstanceForm;

/**
 * Form for modifying/creating monitoring instances
 */
class InstanceConfigForm extends ConfigForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_monitoring_instance');
        $this->setSubmitLabel(t('Save Changes'));
    }

    /**
     * Return a form object for the given instance type
     *
     * @param   string  $type               The instance type for which to return a form
     *
     * @return  Form
     *
     * @throws  InvalidArgumentException    In case the given instance type is invalid
     */
    public function getInstanceForm($type)
    {
        if ($type === 'local') {
            return new LocalInstanceForm();
        } elseif ($type === 'remote') {
            return new RemoteInstanceForm();
        } else {
            throw new InvalidArgumentException(sprintf(t('Invalid instance type "%s" provided'), $type));
        }
    }

    /**
     * Add a new instance
     *
     * The resource to add is identified by the array-key `name'.
     *
     * @param   array   $values             The values to extend the configuration with
     *
     * @return  self
     *
     * @throws  InvalidArgumentException    In case the resource already exists
     */
    public function add(array $values)
    {
        $name = isset($values['name']) ? $values['name'] : '';
        if (! $name) {
            throw new InvalidArgumentException(t('Instance name missing'));
        } elseif ($this->config->get($name) !== null) {
            throw new InvalidArgumentException(t('Instance already exists'));
        }

        unset($values['name']);
        $this->config->{$name} = $values;
        return $this;
    }

    /**
     * Edit an existing instance
     *
     * @param   string      $name           The name of the resource to edit
     * @param   array       $values         The values to edit the configuration with
     *
     * @return  array                       The edited resource configuration
     *
     * @throws  InvalidArgumentException    In case the resource name is missing or invalid
     */
    public function edit($name, array $values)
    {
        if (! $name) {
            throw new InvalidArgumentException(t('Old instance name missing'));
        } elseif (! ($newName = isset($values['name']) ? $values['name'] : '')) {
            throw new InvalidArgumentException(t('New instance name missing'));
        } elseif (! ($instanceConfig = $this->config->get($name))) {
            throw new InvalidArgumentException(t('Unknown instance name provided'));
        }

        unset($values['name']);
        unset($this->config->{$name});
        $this->config->{$newName} = array_merge($instanceConfig->toArray(), $values);
        return $this->config->{$newName};
    }

    /**
     * Remove a instance
     *
     * @param   string      $name           The name of the resource to remove
     *
     * @return  array                       The removed resource confguration
     *
     * @throws  InvalidArgumentException    In case the resource name is missing or invalid
     */
    public function remove($name)
    {
        if (! $name) {
            throw new InvalidArgumentException(t('Instance name missing'));
        } elseif (! ($instanceConfig = $this->config->get($name))) {
            throw new InvalidArgumentException(t('Unknown instance name provided'));
        }

        unset($this->config->{$name});
        return $instanceConfig;
    }

    /**
     * @see Form::onSuccess()
     */
    public function onSuccess(Request $request)
    {
        $instanceName = $request->getQuery('instance');

        try {
            if ($instanceName === null) { // create new instance
                $this->add($this->getValues());
                $message = t('Instance "%s" created successfully.');
            } else { // edit existing instance
                $this->edit($instanceName, $this->getValues());
                $message = t('Instance "%s" edited successfully.');
            }
        } catch (InvalidArgumentException $e) {
            Notification::error($e->getMessage());
            return;
        }

        if ($this->save()) {
            Notification::success(sprintf($message, $this->getElement('name')->getValue()));
        } else {
            return false;
        }
    }

    /**
     * @see Form::onRequest()
     *
     * @throws  ConfigurationError      In case the instance name is missing or invalid
     */
    public function onRequest(Request $request)
    {
        $instanceName = $request->getQuery('instance');
        if ($instanceName !== null) {
            if (! $instanceName) {
                throw new ConfigurationError(t('Instance name missing'));
            } elseif (false === isset($this->config->{$instanceName})) {
                throw new ConfigurationError(t('Unknown instance name provided'));
            }

            $instanceConfig = $this->config->{$instanceName}->toArray();
            $instanceConfig['name'] = $instanceName;
            if (isset($instanceConfig['host'])) {
                // Necessary as we have no config directive for setting the instance's type
                $instanceConfig['type'] = 'remote';
            }
            $this->populate($instanceConfig);
        }
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $instanceType = isset($formData['type']) ? $formData['type'] : 'local';

        $this->addElement(
            'text',
            'name',
            array(
                'required'  => true,
                'label'     => t('Instance Name')
            )
        );
        $this->addElement(
            'select',
            'type',
            array(
                'required'      => true,
                'ignore'        => true,
                'autosubmit'    => true,
                'label'         => t('Instance Type'),
                'description'   => t(
                    'When configuring a remote host, you need to setup passwordless key authentication'
                ),
                'multiOptions'  => array(
                    'local'     => t('Local Command Pipe'),
                    'remote'    => t('Remote Command Pipe')
                ),
                'value'         => $instanceType
            )
        );

        $this->addElements($this->getInstanceForm($instanceType)->createElements($formData)->getElements());
    }
}
