<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config;

use Icinga\Forms\Config\General\ApplicationConfigForm;
use Icinga\Forms\Config\General\LoggingConfigForm;
use Icinga\Forms\ConfigForm;

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
}
