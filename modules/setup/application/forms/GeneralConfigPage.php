<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Icinga\Forms\Config\General\ApplicationConfigForm;
use Icinga\Forms\Config\General\LoggingConfigForm;
use Icinga\Web\Form;

/**
 * Wizard page to define the application and logging configuration
 */
class GeneralConfigPage extends Form
{
    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_general_config');
        $this->setTitle($this->translate('Application Configuration', 'setup.page.title'));
        $this->addDescription($this->translate(
            'Now please adjust all application and logging related configuration options to fit your needs.'
        ));
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $appConfigForm = new ApplicationConfigForm();
        $appConfigForm->createElements($formData);
        $appConfigForm->removeElement('global_module_path');
        $appConfigForm->removeElement('global_config_resource');
        $this->addElements($appConfigForm->getElements());

        $loggingConfigForm = new LoggingConfigForm();
        $this->addElements($loggingConfigForm->createElements($formData)->getElements());
    }
}
