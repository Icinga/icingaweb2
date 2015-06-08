<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Config;

use InvalidArgumentException;
use Icinga\Exception\ConfigurationError;
use Icinga\Forms\ConfigForm;
use Icinga\Module\Monitoring\Command\Transport\LocalCommandFile;
use Icinga\Module\Monitoring\Command\Transport\RemoteCommandFile;
use Icinga\Module\Monitoring\Forms\Config\Instance\LocalInstanceForm;
use Icinga\Module\Monitoring\Forms\Config\Instance\RemoteInstanceForm;
use Icinga\Web\Notification;

/**
 * Form for modifying/creating monitoring instances
 */
class InstanceConfigForm extends ConfigForm
{
    /**
     * (non-PHPDoc)
     * @see Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setName('form_config_monitoring_instance');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * Get a form object for the given instance type
     *
     * @param   string $type                The instance type for which to return a form
     *
     * @return  LocalInstanceForm|RemoteInstanceForm
     *
     * @throws  InvalidArgumentException    In case the given instance type is invalid
     */
    public function getInstanceForm($type)
    {
        switch (strtolower($type)) {
            case LocalCommandFile::TRANSPORT:
                $form = new LocalInstanceForm();
                break;
            case RemoteCommandFile::TRANSPORT;
                $form = new RemoteInstanceForm();
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf($this->translate('Invalid instance type "%s" given'), $type)
                );
        }
        return $form;
    }

    /**
     * Add a new instance
     *
     * The resource to add is identified by the array-key `name'.
     *
     * @param   array   $values             The values to extend the configuration with
     *
     * @return  $this
     *
     * @throws  InvalidArgumentException    In case the resource already exists
     */
    public function add(array $values)
    {
        $name = isset($values['name']) ? $values['name'] : '';
        if (! $name) {
            throw new InvalidArgumentException($this->translate('Instance name missing'));
        }
        if ($this->config->hasSection($name)) {
            throw new InvalidArgumentException($this->translate('Instance already exists'));
        }

        unset($values['name']);
        $this->config->setSection($name, $values);
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
            throw new InvalidArgumentException($this->translate('Old instance name missing'));
        } elseif (! ($newName = isset($values['name']) ? $values['name'] : '')) {
            throw new InvalidArgumentException($this->translate('New instance name missing'));
        } elseif (! $this->config->hasSection($name)) {
            throw new InvalidArgumentException($this->translate('Unknown instance name provided'));
        }

        unset($values['name']);
        $this->config->setSection($name, $values);
        return $this->config->getSection($name);
    }

    /**
     * Remove a instance
     *
     * @param   string      $name           The name of the resource to remove
     *
     * @return  array                       The removed resource configuration
     *
     * @throws  InvalidArgumentException    In case the resource name is missing or invalid
     */
    public function remove($name)
    {
        if (! $name) {
            throw new InvalidArgumentException($this->translate('Instance name missing'));
        } elseif (! $this->config->hasSection($name)) {
            throw new InvalidArgumentException($this->translate('Unknown instance name provided'));
        }

        $instanceConfig = $this->config->getSection($name);
        $this->config->removeSection($name);
        return $instanceConfig;
    }

    /**
     * @see     Form::onRequest()   For the method documentation.
     * @throws  ConfigurationError  In case the instance name is missing or invalid
     */
    public function onRequest()
    {
        $instanceName = $this->request->getQuery('instance');
        if ($instanceName !== null) {
            if (! $instanceName) {
                throw new ConfigurationError($this->translate('Instance name missing'));
            }
            if (! $this->config->hasSection($instanceName)) {
                throw new ConfigurationError($this->translate('Unknown instance name given'));
            }

            $instanceConfig = $this->config->getSection($instanceName)->toArray();
            $instanceConfig['name'] = $instanceName;
            $this->populate($instanceConfig);
        }
    }

    /**
     * (non-PHPDoc)
     * @see Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        $instanceName = $this->request->getQuery('instance');
        try {
            if ($instanceName === null) { // create new instance
                $this->add($this->getValues());
                $message = $this->translate('Instance "%s" created successfully.');
            } else { // edit existing instance
                $this->edit($instanceName, $this->getValues());
                $message = $this->translate('Instance "%s" edited successfully.');
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
     * (non-PHPDoc)
     * @see Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $instanceType = isset($formData['transport']) ? $formData['transport'] : LocalCommandFile::TRANSPORT;

        $this->addElements(array(
            array(
                'text',
                'name',
                array(
                    'required'  => true,
                    'label'     => $this->translate('Instance Name')
                )
            ),
            array(
                'select',
                'transport',
                array(
                    'required'      => true,
                    'autosubmit'    => true,
                    'label'         => $this->translate('Instance Type'),
                    'multiOptions'  => array(
                        LocalCommandFile::TRANSPORT     => $this->translate('Local Command File'),
                        RemoteCommandFile::TRANSPORT    => $this->translate('Remote Command File')
                    ),
                    'value' => $instanceType
                )
            )
        ));

        $this->addElements($this->getInstanceForm($instanceType)->createElements($formData)->getElements());
    }
}
