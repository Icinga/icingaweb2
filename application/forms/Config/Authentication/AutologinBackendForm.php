<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use Zend_Validate_Callback;
use Icinga\Web\Form;

/**
 * Form class for adding/modifying autologin authentication backends
 */
class AutologinBackendForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_authbackend_autologin');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => t('Backend Name'),
                'description'   => t('The name of this authentication backend'),
                'validators'    => array(
                    array(
                        'Regex',
                        false,
                        array(
                            'pattern'  => '/^[^\\[\\]:]+$/',
                            'messages' => array(
                                'regexNotMatch' => 'The backend name cannot contain \'[\', \']\' or \':\'.'
                            )
                        )
                    )
                )
            )
        );
        $this->addElement(
            'text',
            'strip_username_regexp',
            array(
                'required'      => true,
                'label'         => t('Backend Domain Pattern'),
                'description'   => t('The domain pattern of this authentication backend'),
                'value'         => '/\@[^$]+$/',
                'validators'    => array(
                    new Zend_Validate_Callback(function ($value) {
                        return @preg_match($value, '') !== false;
                    })
                )
            )
        );
        $this->addElement(
            'hidden',
            'backend',
            array(
                'required'  => true,
                'value'     => 'autologin'
            )
        );

        return $this;
    }

    /**
     * Validate the configuration by creating a backend and requesting the user count
     *
     * Returns always true as autologin backends are just "passive" backends. (The webserver authenticates users.)
     *
     * @param   Form    $form   The form to fetch the configuration values from
     *
     * @return  bool            Whether validation succeeded or not
     */
    public function isValidAuthenticationBackend(Form $form)
    {
        return true;
    }
}
