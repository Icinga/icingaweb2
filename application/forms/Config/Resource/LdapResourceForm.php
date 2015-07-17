<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Resource;

use Exception;
use Icinga\Web\Form;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
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
     * @see Form::createElements()
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

        if (isset($formData['encryption']) && $formData['encryption'] !== 'none') {
            // TODO(jom): Do not show this checkbox unless the connection is actually failing due to certificate errors
            $this->addElement(
                'checkbox',
                'reqcert',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Require Certificate'),
                    'description'   => $this->translate(
                        'When checked, the LDAP server must provide a valid and known (trusted) certificate.'
                    ),
                    'value'         => 1
                )
            );
        }

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

    /**
     * Validate that the current configuration points to a valid resource
     *
     * @see Form::onSuccess()
     */
    public function onSuccess()
    {
        if (false === static::isValidResource($this)) {
            return false;
        }
    }

    /**
     * Validate the resource configuration by trying to connect with it
     *
     * @param   Form    $form   The form to fetch the configuration values from
     *
     * @return  bool            Whether validation succeeded or not
     */
    public static function isValidResource(Form $form)
    {
        $result = ResourceFactory::createResource(new ConfigObject($form->getValues()))->inspect();
        if ($result->hasError()) {
            $form->addError(sprintf(
                '%s (%s)',
                $form->translate('Connectivity validation failed, connection to the given resource not possible.'),
                $result->getError()
            ));
        }

        // TODO: display diagnostics in $result->toArray() to the user

        return ! $result->hasError();
    }
}
