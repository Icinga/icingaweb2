<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Forms;

use Icinga\Web\Form;
use Icinga\Application\Platform;

/**
 * Wizard page to choose an authentication backend
 */
class AuthenticationPage extends Form
{
    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_authentication_type');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'note',
            'title',
            array(
                'value'         => $this->translate('Authentication', 'setup.page.title'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'h2'))
                )
            )
        );
        $this->addElement(
            'note',
            'description',
            array(
                'value' => $this->translate(
                    'Please choose how you want to authenticate when accessing Icinga Web 2.'
                    . ' Configuring backend specific details follows in a later step.'
                )
            )
        );

        $backendTypes = array();
        if (Platform::hasMysqlSupport() || Platform::hasPostgresqlSupport()) {
            $backendTypes['db'] = $this->translate('Database');
        }
        if (Platform::extensionLoaded('ldap')) {
            $backendTypes['ldap'] = 'LDAP';
        }
        $backendTypes['autologin'] = $this->translate('Autologin');

        $this->addElement(
            'select',
            'type',
            array(
                'required'      => true,
                'label'         => $this->translate('Authentication Type'),
                'description'   => $this->translate('The type of authentication to use when accessing Icinga Web 2'),
                'multiOptions'  => $backendTypes
            )
        );
    }
}
