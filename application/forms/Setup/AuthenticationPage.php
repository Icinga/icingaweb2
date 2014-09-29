<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Setup;

use Icinga\Web\Form;
use Icinga\Application\Platform;
use Icinga\Web\Form\Element\Note;

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
            new Note(
                'description',
                array(
                    'value' => t(
                        'Please choose how you want to authenticate when accessing Icinga Web 2.'
                        . ' Configuring backend specific details follows in a later step.'
                    )
                )
            )
        );

        $backendTypes = array();
        if (Platform::extensionLoaded('pdo') && (Platform::zendClassExists('Zend_Db_Adapter_Pdo_Mysql')
            || Platform::zendClassExists('Zend_Db_Adapter_Pdo_Pgsql')))
        {
            $backendTypes['db'] = t('Database');
        }
        if (Platform::extensionLoaded('ldap')) {
            $backendTypes['ldap'] = 'LDAP';
        }
        $backendTypes['autologin'] = t('Autologin');

        $this->addElement(
            'select',
            'type',
            array(
                'required'      => true,
                'label'         => t('Authentication Type'),
                'description'   => t('The type of authentication to use when accessing Icinga Web 2'),
                'multiOptions'  => $backendTypes
            )
        );
    }
}
