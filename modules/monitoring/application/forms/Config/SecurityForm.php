<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config;

use Zend_Config;
use Icinga\Web\Form;

/**
 * Form for modifying security relevant settings
 */
class SecurityForm extends Form
{
    /**
     * The configuration to use for populating the form
     */
    protected $config;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_monitoring_security');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        return array(
            $this->createElement(
                'text',
                'protected_customvars',
                array(
                    'label'     =>  'Protected Custom Variables',
                    'required'  =>  true,
                    'helptext'  =>  'Comma separated case insensitive list of protected custom variables.'
                        . ' Use * as a placeholder for zero or more wildcard characters.'
                        . ' Existance of those custom variables will be shown, but their values will be masked.'
                )
            )
        );
    }

    /**
     * @see Form::addSubmitButton()
     */
    public function addSubmitButton()
    {
        $this->addElement(
            'submit',
            'btn_submit',
            array(
                'ignore'    => true,
                'label'     => t('Save Changes')
            )
        );

        return $this;
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
