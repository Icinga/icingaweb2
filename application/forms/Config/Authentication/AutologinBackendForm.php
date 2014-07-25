<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use Zend_Validate_Callback;

class AutologinBackendForm extends BaseBackendForm
{
    public function isValidAuthenticationBackend()
    {
        return true;
    }

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
                    'value'         => '',
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
}
