<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Forms\Config\Authentication;

use Zend_Validate_Callback;
use Icinga\Web\Form;

/**
 * Form class for adding/modifying authentication backends of type "external"
 */
class ExternalBackendForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_authbackend_external');
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
                'label'         => $this->translate('Backend Name'),
                'description'   => $this->translate(
                    'The name of this authentication provider that is used to differentiate it from others'
                ),
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
                'label'         => $this->translate('Filter Pattern'),
                'description'   => $this->translate(
                    'The regular expression to use to strip specific parts off from usernames.'
                    . ' Leave empty if you do not want to strip off anything'
                ),
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
                'disabled'  => true,
                'value'     => 'external'
            )
        );

        return $this;
    }

    /**
     * Validate the configuration by creating a backend and requesting the user count
     *
     * Returns always true as backends of type "external" are just "passive" backends.
     *
     * @param   Form    $form   The form to fetch the configuration values from
     *
     * @return  bool            Whether validation succeeded or not
     */
    public static function isValidAuthenticationBackend(Form $form)
    {
        return true;
    }
}
