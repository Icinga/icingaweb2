<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config;

use Icinga\Web\Notification;
use Icinga\Forms\ConfigForm;
use Icinga\Forms\Config\General\LoggingConfigForm;
use Icinga\Forms\Config\General\ApplicationConfigForm;

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
        $this->setSubmitLabel($this->translate('Save Changes'));
        $this->setTitle($this->translate('General Configuration'));
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
    public function onSuccess()
    {
        $sections = array();
        foreach ($this->getValues() as $sectionAndPropertyName => $value) {
            list($section, $property) = explode('_', $sectionAndPropertyName, 2);
            if (! isset($sections[$section])) {
                $sections[$section] = array();
            }
            $sections[$section][$property] = $value;
        }
        foreach ($sections as $section => $config) {
            $this->config->setSection($section, $config);
        }

        if ($this->save()) {
            Notification::success($this->translate('New configuration has successfully been stored'));
        } else {
            return false;
        }
    }

    /**
     * @see Form::onRequest()
     */
    public function onRequest()
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
