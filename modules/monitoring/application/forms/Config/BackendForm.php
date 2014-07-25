<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config;

use Icinga\Web\Form;
use Icinga\Data\ResourceFactory;

/**
 * Form for modifying a monitoring backend
 */
class BackendForm extends Form
{
    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        return array(
            $this->createElement(
                'text',
                'name',
                array(
                    'required'  => true,
                    'label'     => t('Backend Name'),
                    'helptext'  => t('The identifier of this backend')
                )
            ),
            $this->createElement(
                'select',
                'type',
                array(
                    'required'      => true,
                    'class'         => 'autosubmit',
                    'label'         => t('Backend Type'),
                    'helptext'      => t('The data source used for retrieving monitoring information'),
                    'multiOptions'  => array(
                        'ido'           => 'IDO Backend',
                        'statusdat'     => 'Status.dat',
                        'livestatus'    => 'Livestatus'
                    )
                )
            ),
            $this->createElement(
                'select',
                'resource',
                array(
                    'required'      => true,
                    'label'         => t('Resource'),
                    'helptext'      => t('The resource to use'),
                    'multiOptions'  => $this->getResourcesByType(
                        false === isset($formData['type']) || $formData['type'] === 'ido' ? 'db' : $formData['type']
                    )
                )
            ),
            $this->createElement(
                'checkbox',
                'disabled',
                array(
                    'required'  => true,
                    'label'     => t('Disable This Backend')
                )
            )
        );
    }

    /**
     * @see Form::addSubmitButton()
     */
    public function addSubmitButton()
    {
        $this->addElement(
            'submit',
            'btn_submit',
            array(
                'label' => t('Save Changes')
            )
        );

        return $this;
    }

    /**
     * Return the backend configuration values and its name
     *
     * The first value is the name and the second one the values as array.
     *
     * @return  array
     */
    public function getBackendConfig()
    {
        $values = $this->getValues();
        $name = $values['name'];

        if ($values['disabled'] == '0') {
            unset($values['disabled']);
        }

        unset($values['name']);
        unset($values['btn_submit']);
        unset($values[$this->getTokenElementName()]);
        return array($name, $values);
    }

    /**
     * Populate the form with the given configuration values
     *
     * @param   string  $name       The name of the backend
     * @param   array   $config     The configuration values
     */
    public function setBackendConfig($name, array $config)
    {
        $config['name'] = $name;
        $this->populate($config);
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
        foreach (array_keys(ResourceFactory::getResourceConfigs($type)->toArray()) as $name) {
            $backends[$name] = $name;
        }

        return $backends;
    }
}
