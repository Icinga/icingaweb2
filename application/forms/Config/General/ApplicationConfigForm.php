<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\General;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Data\ResourceFactory;
use Icinga\Web\Form;
use ipl\Html\Text;

/**
 * Configuration form for general application options
 *
 * This form is not used directly but as subform to the {@link GeneralConfigForm}.
 */
class ApplicationConfigForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_config_general_application');
    }

    /**
     * {@inheritdoc}
     *
     * @return  $this
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'checkbox',
            'global_show_stacktraces',
            array(
                'value'         => true,
                'label'         => $this->translate('Show Stacktraces'),
                'description'   => $this->translate(
                    'Set whether to show an exception\'s stacktrace by default. This can also'
                    . ' be set in a user\'s preferences with the appropriate permission.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'global_show_application_state_messages',
            array(
                'value'         => true,
                'label'         => $this->translate('Show Application State Messages'),
                'description'   => $this->translate(
                    "Set whether to show application state messages."
                    . " This can also be set in a user's preferences."
                )
            )
        );

        $this->addElement(
            'checkbox',
            'security_use_strict_csp',
            [
                'label'         => $this->translate('Enable strict content security policy'),
                'description'   => $this->translate(
                    'Set whether to use strict content security policy (CSP).'
                    . ' This setting helps to protect from cross-site scripting (XSS).'
                )
            ]
        );

        $this->addElement(
            'text',
            'global_module_path',
            array(
                'label'         => $this->translate('Module Path'),
                'required'      => true,
                'value'         => implode(':', Icinga::app()->getModuleManager()->getModuleDirs()),
                'description'   => $this->translate(
                    'Contains the directories that will be searched for available modules, separated by '
                    . 'colons. Modules that don\'t exist in these directories can still be symlinked in '
                    . 'the module folder, but won\'t show up in the list of disabled modules.'
                )
            )
        );

        $backends = array_keys(ResourceFactory::getResourceConfigs()->toArray());
        $backends = array_combine($backends, $backends);

        $this->addElement(
            'select',
            'global_config_resource',
            array(
                'required'      => true,
                'multiOptions'  => array_merge(
                    ['' => sprintf(' - %s - ', $this->translate('Please choose'))],
                    $backends
                ),
                'disable'       => [''],
                'value'         => '',
                'label'         => $this->translate('Configuration Database')
            )
        );

        $config = Config::app()->getSection('global');
        if (!isset($config->config_resource)) {
            $missingConfigResource =
                Text::create(
                    $this->translate("No Configuration Database selected. 
                    Please set the field to establish a valid database connection.")
                );
            $this->warning($missingConfigResource, false);
        }

        return $this;
    }
}
