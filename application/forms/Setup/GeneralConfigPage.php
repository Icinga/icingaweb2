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
        $this->setName('setup_general_config');
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

        // TODO: This is splitted as not all elements are required (as of d201cff)
        $appForm = new ApplicationConfigForm();
        $appForm->createElements($formData);
        $this->addElement($appForm->getElement('global_modulePath'));
        $this->addElement($appForm->getElement('global_filemode'));

        $loggingForm = new LoggingConfigForm();
        $this->addElements($loggingForm->createElements($formData)->getElements());
    }
}
