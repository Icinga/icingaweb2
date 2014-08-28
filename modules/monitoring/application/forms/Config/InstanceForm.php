<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config;

use Icinga\Web\Form;
use Icinga\Web\Form\Element\Number;
use Icinga\Web\Form\Decorator\HelpText;
use Icinga\Web\Form\Decorator\ElementWrapper;

/**
 * Form for modifying/creating monitoring instances
 */
class InstanceForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_monitoring_instances');
        $this->setSubmitLabel(t('Save Changes'));
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $elements = array(
            $this->createElement(
                'text',
                'name',
                array(
                    'required'  => true,
                    'label'     => t('Instance Name')
                )
            ),
            $this->createElement(
                'select',
                'type',
                array(
                    'required'      => true,
                    'ignore'        => true,
                    'label'         => t('Instance Type'),
                    'class'         => 'autosubmit',
                    'helptext'      => t(
                        'When configuring a remote host, you need to setup passwordless key authentication'
                    ),
                    'multiOptions'  => array(
                        'local'     => t('Local Command Pipe'),
                        'remote'    => t('Remote Command Pipe')
                    )
                )
            )
        );

        if (isset($formData['type']) && $formData['type'] === 'remote') {
            $elements[] = $this->createElement(
                'text',
                'host',
                array(
                    'required'  => true,
                    'label'     => t('Remote Host'),
                    'helptext'  => t(
                        'Enter the hostname or address of the machine on which the icinga instance is running'
                    )
                )
            );
            $elements[] = new Number(
                array(
                    'required'      => true,
                    'name'          => 'port',
                    'label'         => t('Remote SSH Port'),
                    'helptext'      => t('Enter the ssh port to use for connecting to the remote icinga instance'),
                    'value'         => 22,
                    'decorators'    => array( // The order is important!
                        'ViewHelper',
                        'Errors',
                        new ElementWrapper(),
                        new HelpText()
                    )
                )
            );
            $elements[] = $this->createElement(
                'text',
                'user',
                array(
                    'required'      => true,
                    'label'         => t('Remote SSH User'),
                    'helptext'      => t(
                        'Enter the username to use for connecting to the remote machine or leave blank for default'
                    )
                )
            );
        } else {
            // TODO(5967,5525): Without this element, switching the type to "local" causes
            //                  the form to be processed as it's complete in that case.
            $elements[] = $this->createElement(
                'hidden',
                'hitchhiker',
                array(
                    'required'  => true,
                    'ignore'    => true,
                    'value'     => 'Arthur'
                )
            );
        }

        $elements[] = $this->createElement(
            'text',
            'path',
            array(
                'required'  => true,
                'label'     => t('Pipe Filepath'),
                'value'     => '/usr/local/icinga/var/rw/icinga.cmd',
                'helptext'  => t('The file path where the icinga commandpipe can be found')
            )
        );
        return $elements;
    }

    /**
     * Return the instance configuration values and its name
     *
     * The first value is the name and the second one the values as array.
     *
     * @return  array
     */
    public function getInstanceConfig()
    {
        $values = $this->getValues();
        $name = $values['name'];
        unset($values['name']);
        return array($name, $values);
    }

    /**
     * Populate the form with the given configuration values
     *
     * @param   string  $name       The name of the instance
     * @param   array   $config     The configuration values
     */
    public function setInstanceConfig($name, array $config)
    {
        $config['name'] = $name;

        if (isset($config['host'])) {
            // Necessary as we have no config directive for setting the instance's type
            $config['type'] = 'remote';
        }

        $this->populate($config);
    }
}
