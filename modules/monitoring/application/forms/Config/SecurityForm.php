<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config;

use Zend_Config;
use Icinga\Web\Form;

class SecurityForm extends Form
{
    /**
     * The configuration to use for populating the form
     */
    protected $config;

    /**
     * Create this form
     *
     * @see Icinga\Web\Form::create
     */
    public function create()
    {
        $this->addElement(
            'text',
            'protected_customvars',
            array(
                'label'     =>  'Protected Custom Variables',
                'required'  =>  true,
                'value'     =>  $this->config->protected_customvars
            )
        );
        $this->setSubmitLabel('{{SAVE_ICON}} Save');
    }

    /**
     * Set the configuration to be used for initial population of the form
     */
    public function setConfiguration($config)
    {
        $this->config = $config;
    }

    /**
     * Return the configuration set by this form
     *
     * @return Zend_Config The configuration set in this form
     */
    public function getConfig()
    {
        $values = $this->getValues();
        return new Zend_Config(array(
            'protected_customvars' => $values['protected_customvars']
        ));
    }
}
