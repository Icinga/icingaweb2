<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Resource;

use Icinga\Web\Form;
use Icinga\Protocol\Ldap\LdapConnection;

/**
 * Form class for adding/modifying ldap resources
 */
class LdapResourceForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_resource_ldap');
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData   The data sent by the user
     */
    public function createElements(array $formData)
    {
        $defaultPort = ! array_key_exists('encryption', $formData) || $formData['encryption'] !== LdapConnection::LDAPS
            ? 389
            : 636;

        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Resource Name'),
                'description'   => $this->translate('The unique name of this resource')
            )
        );
        $this->addElement(
            'text',
            'hostname',
            array(
                'required'      => true,
                'label'         => $this->translate('Host'),
                'description'   => $this->translate(
                    'The hostname or address of the LDAP server to use for authentication'
                ),
                'value'         => 'localhost'
            )
        );
        $this->addElement(
            'number',
            'port',
            array(
                'required'          => true,
                'preserveDefault'   => true,
                'label'             => $this->translate('Port'),
                'description'       => $this->translate('The port of the LDAP server to use for authentication'),
                'value'             => $defaultPort
            )
        );
        $this->addElement(
            'select',
            'encryption',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'label'         => $this->translate('Encryption'),
                'description'   => $this->translate(
                    'Whether to encrypt communication. Choose STARTTLS or LDAPS for encrypted communication or'
                    . ' none for unencrypted communication'
                ),
                'multiOptions'  => array(
                    'none'                      => $this->translate('None', 'resource.ldap.encryption'),
                    LdapConnection::STARTTLS    => 'STARTTLS',
                    LdapConnection::LDAPS       => 'LDAPS'
                )
            )
        );

        $this->addElement(
            'text',
            'root_dn',
            array(
                'required'      => true,
                'label'         => $this->translate('Root DN'),
                'description'   => $this->translate(
                    'Only the root and its child nodes will be accessible on this resource.'
                )
            )
        );
        $this->addElement(
            'text',
            'bind_dn',
            array(
                'label'         => $this->translate('Bind DN'),
                'description'   => $this->translate(
                    'The user dn to use for querying the ldap server. Leave the dn and password empty for attempting'
                    . ' an anonymous bind'
                )
            )
        );
        $this->addElement(
            'password',
            'bind_pw',
            array(
                'renderPassword'    => true,
                'label'             => $this->translate('Bind Password'),
                'description'       => $this->translate('The password to use for querying the ldap server')
            )
        );

        return $this;
    }
}
