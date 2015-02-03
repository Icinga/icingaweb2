<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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

        if (isset($formData['type']) && $formData['type'] === 'external' && !isset($_SERVER['REMOTE_USER'])) {
            $this->addElement(
                'note',
                'external_note',
                array(
                    'value'         => $this->translate(
                        'You\'re currently not authenticated using any of the web server\'s authentication '
                        . 'mechanisms. Make sure you\'ll configure such, otherwise you\'ll not be able to '
                        . 'log into Icinga Web 2.'
                    ),
                    'decorators'    => array(
                        'ViewHelper',
                        array(
                            'HtmlTag',
                            array('tag' => 'p', 'class' => 'icon-info info-box')
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
        $backendTypes['external'] = $this->translate('External');

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
