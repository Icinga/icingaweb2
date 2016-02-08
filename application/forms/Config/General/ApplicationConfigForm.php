<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\General;

use Icinga\Application\Icinga;
use Icinga\Data\ResourceFactory;
use Icinga\Web\Form;

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
                'required'      => true,
                'value'         => true,
                'label'         => $this->translate('Show Stacktraces'),
                'description'   => $this->translate(
                    'Set whether to show an exception\'s stacktrace by default. This can also'
                    . ' be set in a user\'s preferences with the appropriate permission.'
                )
            )
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

        $this->addElement(
            'select',
            'global_config_backend',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'label'         => $this->translate('User Preference Storage Type'),
                'multiOptions'  => array(
                    'ini'   => $this->translate('File System (INI Files)'),
                    'db'    => $this->translate('Database'),
                    'none'  => $this->translate('Don\'t Store Preferences')
                )
            )
        );

        if (isset($formData['global_config_backend']) && $formData['global_config_backend'] === 'db') {
            $backends = array();
            foreach (ResourceFactory::getResourceConfigs()->toArray() as $name => $resource) {
                if ($resource['type'] === 'db') {
                    $backends[$name] = $name;
                }
            }

            $this->addElement(
                'select',
                'global_config_resource',
                array(
                    'required'      => true,
                    'multiOptions'  => $backends,
                    'label'         => $this->translate('Database Connection')
                )
            );
        }

        return $this;
    }
}
