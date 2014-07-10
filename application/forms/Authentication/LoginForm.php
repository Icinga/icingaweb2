<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Authentication;

use Icinga\Web\Form;

/**
 * Class LoginForm
 */
class LoginForm extends Form
{
    /**
     * Initialize this login form
     */
    public function init()
    {
        $this->setTokenDisabled();
        $this->setName('form_login');
        $this->setSubmitLabel('Login');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements()
    {
        return array(
            $this->createElement(
                'text',
                'username',
                array(
                    'label'       => t('Username'),
                    'placeholder' => t('Please enter your username...'),
                    'required'    => true
                )
            ),
            $this->createElement(
                'password',
                'password',
                array(
                    'label'       => t('Password'),
                    'placeholder' => t('...and your password'),
                    'required'    => true
                )
            )
        );
    }

    /**
     * @see Form::applyValues()
     */
    public function applyValues(array $values)
    {
        parent::applyValues($values);

        if (isset($values['username'])) {
            $this->getElement('password')->setAttrib('class', 'autofocus');
        } else {
            $this->getElement('username')->setAttrib('class', 'autofocus');
        }

        return $this;
    }
}
