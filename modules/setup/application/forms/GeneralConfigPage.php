<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'note',
            'title',
            array(
                'value'         => $this->translate('Application Configuration', 'setup.page.title'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'h2'))
                )
            )
        );
        $this->addElement(
            'note',
            'description',
            array(
                'value' => $this->translate(
                    'Now please adjust all application and logging related configuration options to fit your needs.'
                )
            )
        );

        $loggingForm = new LoggingConfigForm();
        $this->addElements($loggingForm->createElements($formData)->getElements());
    }
}
