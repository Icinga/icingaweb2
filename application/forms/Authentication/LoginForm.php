<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Forms\Authentication;

use Icinga\Web\Form;
use Icinga\Web\Url;

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
        $this->setSubmitLabel($this->translate('Login'));
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'username',
            array(
                'required'      => true,
                'label'         => $this->translate('Username'),
                'placeholder'   => $this->translate('Please enter your username...'),
                'class'         => false === isset($formData['username']) ? 'autofocus' : ''
            )
        );
        $this->addElement(
            'password',
            'password',
            array(
                'required'      => true,
                'label'         => $this->translate('Password'),
                'placeholder'   => $this->translate('...and your password'),
                'class'         => isset($formData['username']) ? 'autofocus' : ''
            )
        );
        $this->addElement(
            'hidden',
            'redirect',
            array(
                'value' => Url::fromRequest()->getParam('redirect')
            )
        );
    }
}
