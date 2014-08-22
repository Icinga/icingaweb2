<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config;

use Icinga\Web\Form;

/**
 * Form for modifying security relevant settings
 */
class SecurityForm extends Form
{
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
}
