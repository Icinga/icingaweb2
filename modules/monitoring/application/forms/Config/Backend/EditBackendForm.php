<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config\Backend;

use Zend_Config;
use Icinga\Web\Form;
use Icinga\Application\Icinga;
use Icinga\Data\ResourceFactory;

/**
 * Form for modifying a monitoring backend
 */
class EditBackendForm extends Form
{
    /**
     * Database resources to use instead of the one's from ResourceFactory (used for testing)
     *
     * @var array
     */
    protected $resources;

    /**
     * The Backend configuration to use for populating the form
     *
     * @var Zend_Config
     */
    protected $backend;

    /**
     * Set the configuration to be used for initial population of the form
     *
     * @param Zend_Form $config
     */
    public function setBackendConfiguration($config)
    {
        $this->backend = $config;
    }

    /**
     * Set a custom array of resources to be used in this form instead of the ones from ResourceFactory
     * (used for testing)
     */
    public function setResources($resources)
    {
        $this->resources = $resources;
    }

    /**
     * Return content of the resources.ini or previously set resources for displaying in the database selection field
     *
     * @return array
     */
    public function getResources()
    {
        if ($this->resources === null) {
            return ResourceFactory::getResourceConfigs()->toArray();
        } else {
            return $this->resources;
        }
    }

    /**
     * Return a list of all resources of the given type ready to be used as content for a select input
     *
     * @param   string  $type   The type of resources to return
     *
     * @return  array
     */
    protected function getResourcesByType($type)
    {
        $backends = array();
        foreach ($this->getResources() as $name => $resource) {
            if ($resource['type'] === $type) {
                $backends[$name] = $name;
            }
        }

        return $backends;
    }

    /**
     * Create this form
     *
     * @see Icinga\Web\Form::create()
     */
    public function create()
    {
        $backendType = $this->getRequest()->getParam('backend_type', $this->backend->type);

        $this->addElement(
            'select',
            'backend_type',
            array(
                'label'         =>  'Backend Type',
                'value'         =>  $this->backend->type,
                'required'      =>  true,
                'helptext'      =>  'The data source used for retrieving monitoring information',
                'multiOptions'  =>  array(
                    'ido'           => 'IDO Backend',
                    'statusdat'     => 'Status.dat',
                    'livestatus'    => 'Livestatus'
                )
            )
        );
        $this->addElement(
            'select',
            'backend_resource',
            array(
                'label'         => 'Resource',
                'value'         => $this->backend->resource,
                'required'      => true,
                'multiOptions'  => $this->getResourcesByType($backendType === 'ido' ? 'db' : $backendType),
                'helptext'      => 'The resource to use'
            )
        );
        $this->addElement(
            'checkbox',
            'backend_disable',
            array(
                'label'     => 'Disable This Backend',
                'required'  =>  true,
                'value'     =>  $this->backend->disabled
            )
        );

        $this->enableAutoSubmit(array('backend_type'));
        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
    }

    /**
     * Return a configuration containing the backend settings entered in this form
     *
     * @return Zend_Config The updated configuration for this backend
     */
    public function getConfig()
    {
        $values = $this->getValues();
        return new Zend_Config(
            array(
                'type'     => $values['backend_type'],
                'disabled' => $values['backend_disable'],
                'resource' => $values['backend_resource']
            )
        );
    }
}
