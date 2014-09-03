<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\General;

use DateTimeZone;
use Icinga\Web\Form;
use Icinga\Util\Translator;
use Icinga\Data\ResourceFactory;

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
        $languages = array();
        foreach (Translator::getAvailableLocaleCodes() as $language) {
            $languages[$language] = $language;
        }

        $this->addElement(
            'select',
            'global_language',
            array(
                'label'         => t('Default Language'),
                'required'      => true,
                'multiOptions'  => $languages,
                'description'   => t(
                    'Select the language to use by default. Can be overwritten by a user in his preferences.'
                )
            )
        );

        $tzList = array();
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $tzList[$tz] = $tz;
        }

        $this->addElement(
            'select',
            'global_timezone',
            array(
                'label'         => t('Default Application Timezone'),
                'required'      => true,
                'multiOptions'  => $tzList,
                'description'   => t(
                    'Select the timezone to be used as the default. User\'s can set their own timezone if'
                    . ' they like to, but this is the timezone to be used as the default setting .'
                ),
                'value'         => date_default_timezone_get()
            )
        );

        $this->addElement(
            'text',
            'global_modulePath',
            array(
                'label'         => t('Module Path'),
                'required'      => true,
                'description'   => t(
                    'Contains the directories that will be searched for available modules, separated by '
                    . 'colons. Modules that don\'t exist in these directories can still be symlinked in '
                    . 'the module folder, but won\'t show up in the list of disabled modules.'
                ),
                'value'     => realpath(ICINGAWEB_APPDIR . '/../modules')
            )
        );

        $this->addElement(
            'select',
            'preferences_type',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'label'         => t('User Preference Storage Type'),
                'multiOptions'  => array(
                    'ini'   => t('File System (INI Files)'),
                    'db'    => t('Database'),
                    'null'  => t('Don\'t Store Preferences')
                )
            )
        );
        if (isset($formData['preferences_type']) && $formData['preferences_type'] === 'db') {
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
                    'label'         => t('Database Connection')
                )
            );
        }

        return $this;
    }
}
