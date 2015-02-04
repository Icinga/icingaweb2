<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\General;

use Icinga\Application\Icinga;
use Icinga\Data\ResourceFactory;
use Icinga\Web\Form;


/**
 * Form class to modify the general application configuration
 */
class ApplicationConfigForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_general_application');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
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
            'preferences_store',
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
        if (isset($formData['preferences_store']) && $formData['preferences_store'] === 'db') {
            $backends = array();
            foreach (ResourceFactory::getResourceConfigs()->toArray() as $name => $resource) {
                if ($resource['type'] === 'db') {
                    $backends[$name] = $name;
                }
            }

            $this->addElement(
                'select',
                'preferences_resource',
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
