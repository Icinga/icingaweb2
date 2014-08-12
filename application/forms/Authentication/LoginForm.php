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
        $this->setName('form_login');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements($formData)
    {
        return array(
            $this->createElement(
                'text',
                'username',
                array(
                    'required'      => true,
                    'label'         => t('Username'),
                    'placeholder'   => t('Please enter your username...'),
                    'class'         => false === isset($formData['username']) ? 'autofocus' : ''
                )
            ),
            $this->createElement(
                'password',
                'password',
                array(
                    'required'      => true,
                    'label'         => t('Password'),
                    'placeholder'   => t('...and your password'),
                    'class'         => isset($formData['username']) ? 'autofocus' : ''
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
                'label'     => t('Login')
            )
        );
    }
}
