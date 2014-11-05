<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

use Icinga\Web\Request;
use Icinga\Web\Notification;
use Icinga\Form\ConfigForm;
use Icinga\Form\Config\General\LoggingConfigForm;
use Icinga\Form\Config\General\ApplicationConfigForm;

/**
 * Form class for application-wide and logging specific settings
 */
class GeneralConfigForm extends ConfigForm
{
    /**
     * Initialize this configuration form
     */
    public function init()
    {
        $this->setName('form_config_general');
        $this->setSubmitLabel(t('Save Changes'));
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $appConfigForm = new ApplicationConfigForm();
        $loggingConfigForm = new LoggingConfigForm();
        $this->addElements($appConfigForm->createElements($formData)->getElements());
        $this->addElements($loggingConfigForm->createElements($formData)->getElements());
    }

    /**
     * @see Form::onSuccess()
     */
    public function onSuccess(Request $request)
    {
        $sections = array();
        foreach ($this->getValues() as $sectionAndPropertyName => $value) {
            list($section, $property) = explode('_', $sectionAndPropertyName);
            if (! isset($sections[$section])) {
                $sections[$section] = array();
            }
            $sections[$section][$property] = $value;
        }
        foreach ($sections as $section => $config) {
            $this->config->{$section} = $config;
        }

        if ($this->save()) {
            Notification::success(t('New configuration has successfully been stored'));
        } else {
            return false;
        }
    }

    /**
     * @see Form::onRequest()
     */
    public function onRequest(Request $request)
    {
        $values = array();
        foreach ($this->config as $section => $properties) {
            foreach ($properties as $name => $value) {
                $values[$section . '_' . $name] = $value;
            }
        }

        $this->populate($values);
    }
}
