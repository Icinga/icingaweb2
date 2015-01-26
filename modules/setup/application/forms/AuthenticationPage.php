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

        if (isset($formData['type']) && $formData['type'] === 'autologin' && !isset($_SERVER['REMOTE_USER'])) {
            $this->addElement(
                'note',
                'autologin_note',
                array(
                    'value'         => sprintf(
                        $this->translate(
                            'You\'re currently not authenticated using any of the web server\'s authentication '
                            . 'mechanisms. Make sure you\'ll configure such either by using the %s or by setting'
                            . ' it up manually, otherwise you\'ll not be able to log into Icinga Web 2 once the '
                            . 'wizard is complete.'
                        ),
                        '<em title="icingacli help setup config webserver">IcingaCLI</em>'
                    ),
                    'decorators'    => array(
                        'ViewHelper',
                        array(
                            'HtmlTag',
                            array('tag' => 'p', 'class' => 'icon-info info')
                        )
                    )
                )
            );
        }

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
                'autosubmit'    => true,
                'label'         => $this->translate('Authentication Type'),
                'description'   => $this->translate('The type of authentication to use when accessing Icinga Web 2'),
                'multiOptions'  => $backendTypes
            )
        );
    }
}
