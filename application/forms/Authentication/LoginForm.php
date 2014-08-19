<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Authentication;

use Icinga\Web\Form;
use Icinga\Web\Url;

/**
 * Class LoginForm
 */
class LoginForm extends Form
{
    /**
     * Interface how the form should be created
     */
    protected function create()
    {
        $url = Url::fromRequest();
        
        $this->setName('form_login');
        $this->addElement('text', 'username', array(
            'label'       => t('Username'),
            'placeholder' => t('Please enter your username...'),
            'required'    => true,
        ));
        $redir = $this->addElement('hidden', 'redirect');
        $redirectUrl = $url->shift('redirect');
        if ($redirectUrl) {
            $this->setDefault('redirect', $redirectUrl);
        }

        $this->addElement('password', 'password', array(
            'label'       => t('Password'),
            'placeholder' => t('...and your password'),
            'required'    => true
        ));
        // TODO: We need a place to intercept filled forms before rendering
        if ($this->getRequest()->getPost('username') !== null) {
            $this->getElement('password')->setAttrib('class', 'autofocus');
        } else {
            $this->getElement('username')->setAttrib('class', 'autofocus');
        }
        $this->setAction((string) $url);
        $this->setSubmitLabel('Login');
    }
}
