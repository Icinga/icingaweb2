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
     * Disable CSRF protection
     * @var bool
     */
    protected $tokenDisabled = true;

    /**
     * Interface how the form should be created
     */
    protected function create()
    {
        $this->setName('form_login');
        $this->addElement('text', 'username', array(
            'label'       => t('Username'),
            'placeholder' => t('Please enter your username...'),
            'required'    => true,
        ));

        $this->addElement('password', 'password', array(
            'label'       => t('Password'),
            'placeholder' => t('...and your password'),
            'required'    => true
        ));
        // TODO: We need a place to intercept filled forms before rendering
        if (isset($_POST['username'])) {
            $this->getElement('password')->setAttrib('class', 'autofocus');
        } else {
            $this->getElement('username')->setAttrib('class', 'autofocus');
        }
        $this->setSubmitLabel('Login');
    }
}
