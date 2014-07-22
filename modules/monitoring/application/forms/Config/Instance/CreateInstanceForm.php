<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config\Instance;

use \Icinga\Web\Form;
use \Zend_Config;

/**
 * Form for creating new instances
 *
 * @see EditInstanceForm
 */
class CreateInstanceForm extends EditInstanceForm
{

    /**
     * Create the form elements
     *
     * @see EditInstanceForm::create()
     */
    public function create()
    {
        $this->setInstanceConfiguration(new Zend_Config(array()));
        $this->addElement(
            'text',
            'instance_name',
            array(
                'label'     =>  'Instance Name',
                'helptext'  =>  'Please enter the name for the instance to create'
            )
        );
        parent::create();
    }

    /**
     * Return the name of the instance to be created
     *
     * @return string The name of the instance as entered in the form
     */
    public function getInstanceName()
    {
        return $this->getValue('instance_name');
    }
}
