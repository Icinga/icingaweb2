<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use Zend_Validate_Callback;

/**
 * Form class for adding/modifying autologin authentication backends
 */
class AutologinBackendForm extends BaseBackendForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_authentication_autologin');
        $this->setSubmitLabel(t('Save Changes'));
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        return array(
            $this->createElement(
                'text',
                'name',
                array(
                    'required'      => true,
                    'allowEmpty'    => false,
                    'label'         => t('Backend Name'),
                    'helptext'      => t('The name of this authentication backend'),
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
            ),
            $this->createElement(
                'text',
                'strip_username_regexp',
                array(
                    'required'      => true,
                    'allowEmpty'    => false,
                    'label'         => t('Backend Domain Pattern'),
                    'helptext'      => t('The domain pattern of this authentication backend'),
                    'value'         => '/\@[^$]+$/',
                    'validators'    => array(
                        new Zend_Validate_Callback(function ($value) {
                            return @preg_match($value, '') !== false;
                        })
                    )
                )
            ),
            $this->createElement(
                'hidden',
                'backend',
                array(
                    'required'  => true,
                    'value'     => 'autologin'
                )
            )
        );
    }

    /**
     * Validate the configuration state of this backend
     *
     * Returns just true as autologins are being handled externally by the webserver.
     *
     * @return  true
     */
    public function isValidAuthenticationBackend()
    {
        return true;
    }
}
