<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Setup;

use Icinga\Web\Form;
use Icinga\Web\Form\Element\Note;
use Icinga\Form\Config\General\LoggingConfigForm;
use Icinga\Form\Config\General\ApplicationConfigForm;

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
        $this->setName('setup_application_config');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            new Note(
                'description',
                array(
                    'value' => t(
                        'Now please adjust all application and logging related configuration options to fit your needs.'
                    )
                )
            )
        );

        $appForm = new ApplicationConfigForm();
        $this->addElement($appForm->createElements($formData)->getElement('global_modulePath'));

        $loggingForm = new LoggingConfigForm();
        $this->addElements($loggingForm->createElements($formData)->getElements());
    }
}
