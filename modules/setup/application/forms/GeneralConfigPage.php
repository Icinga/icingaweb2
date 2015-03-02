<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Icinga\Web\Form;
use Icinga\Forms\Config\General\LoggingConfigForm;

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
        $loggingForm = new LoggingConfigForm();
        $this->addElements($loggingForm->createElements($formData)->getElements());
    }
}
