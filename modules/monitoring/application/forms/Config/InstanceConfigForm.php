<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Config;

use InvalidArgumentException;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfigForm;
use Icinga\Module\Monitoring\Command\Transport\LocalCommandFile;
use Icinga\Module\Monitoring\Command\Transport\RemoteCommandFile;
use Icinga\Module\Monitoring\Forms\Config\Instance\LocalInstanceForm;
use Icinga\Module\Monitoring\Forms\Config\Instance\RemoteInstanceForm;

/**
 * Form for managing monitoring instances
 */
class InstanceConfigForm extends ConfigForm
{
    /**
     * The instance to load when displaying the form for the first time
     *
     * @var string
     */
    protected $instanceToLoad;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_monitoring_instance');
        $this->setSubmitLabel($this->translate('Save Changes'));
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
        switch (strtolower($type)) {
            case LocalCommandFile::TRANSPORT:
                return new LocalInstanceForm();
            case RemoteCommandFile::TRANSPORT;
                return new RemoteInstanceForm();
            default:
                throw new InvalidArgumentException(
                    sprintf($this->translate('Invalid monitoring instance type "%s" given'), $type)
                );
        }
    }

    /**
     * Populate the form with the given instance's config
     *
     * @param   string  $name
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no instance with the given name is found
     */
    public function load($name)
    {
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No monitoring instance called "%s" found', $name);
        }

        $this->instanceToLoad = $name;
        return $this;
    }

    /**
     * Add a new instance
     *
     * The instance to add is identified by the array-key `name'.
     *
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  InvalidArgumentException    In case $data does not contain a instance name
     * @throws  IcingaException             In case a instance with the same name already exists
     */
    public function add(array $data)
    {
        if (! isset($data['name'])) {
            throw new InvalidArgumentException('Key \'name\' missing');
        }

        $instanceName = $data['name'];
        if ($this->config->hasSection($instanceName)) {
            throw new IcingaException(
                $this->translate('A monitoring instance with the name "%s" does already exist'),
                $instanceName
            );
        }

        unset($data['name']);
        $this->config->setSection($instanceName, $data);
        return $this;
    }

    /**
     * Edit an existing instance
     *
     * @param   string  $name
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no instance with the given name is found
     */
    public function edit($name, array $data)
    {
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No monitoring instance called "%s" found', $name);
        }

        $instanceConfig = $this->config->getSection($name);
        if (isset($data['name'])) {
            if ($data['name'] !== $name) {
                $this->config->removeSection($name);
                $name = $data['name'];
            }

            unset($data['name']);
        }

        $instanceConfig->merge($data);
        foreach ($instanceConfig->toArray() as $k => $v) {
            if ($v === null) {
                unset($instanceConfig->$k);
            }
        }

        $this->config->setSection($name, $instanceConfig);
        return $this;
    }

    /**
     * Remove a instance
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
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Instance Name'),
                'description'   => $this->translate(
                    'The name of this monitoring instance that is used to differentiate it from others'
                ),
                'validators'    => array(
                    array(
                        'Regex',
                        false,
                        array(
                            'pattern'  => '/^[^\\[\\]:]+$/',
                            'messages' => array(
                                'regexNotMatch' => $this->translate(
                                    'The name cannot contain \'[\', \']\' or \':\'.'
                                )
                            )
                        )
                    )
                )
            )
        );

        $instanceTypes = array(
            LocalCommandFile::TRANSPORT     => $this->translate('Local Command File'),
            RemoteCommandFile::TRANSPORT    => $this->translate('Remote Command File')
        );

        $instanceType = isset($formData['transport']) ? $formData['transport'] : null;
        if ($instanceType === null) {
            $instanceType = key($instanceTypes);
        }

        $this->addElements(array(
            array(
                'select',
                'transport',
                array(
                    'required'      => true,
                    'autosubmit'    => true,
                    'label'         => $this->translate('Instance Type'),
                    'description'   => $this->translate('The type of transport to use for this monitoring instance'),
                    'multiOptions'  => $instanceTypes
                )
            )
        ));

        $this->addSubForm($this->getInstanceForm($instanceType)->create($formData), 'instance_form');
    }

    /**
     * Populate the configuration of the instance to load
     */
    public function onRequest()
    {
        if ($this->instanceToLoad) {
            $data = $this->config->getSection($this->instanceToLoad)->toArray();
            $data['name'] = $this->instanceToLoad;
            $this->populate($data);
        }
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
        $values = array_merge($values, $values['instance_form']);
        unset($values['instance_form']);
        return $values;
    }
}
