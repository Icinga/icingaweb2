<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Forms\Config\Resource;

use Exception;
use Icinga\Web\Form;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;

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
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => t('Resource Name'),
                'description'   => t('The unique name of this resource')
            )
        );
        $this->addElement(
            'text',
            'hostname',
            array(
                'required'      => true,
                'label'         => t('Host'),
                'description'   => t('The hostname or address of the LDAP server to use for authentication'),
                'value'         => 'localhost'
            )
        );
        $this->addElement(
            'number',
            'port',
            array(
                'required'      => true,
                'label'         => t('Port'),
                'description'   => t('The port of the LDAP server to use for authentication'),
                'value'         => 389
            )
        );
        $this->addElement(
            'text',
            'root_dn',
            array(
                'required'      => true,
                'label'         => t('Root DN'),
                'description'   => t('Only the root and its child nodes will be accessible on this resource.')
            )
        );
        $this->addElement(
            'text',
            'bind_dn',
            array(
                'required'      => true,
                'label'         => t('Bind DN'),
                'description'   => t('The user dn to use for querying the ldap server')
            )
        );
        $this->addElement(
            'password',
            'bind_pw',
            array(
                'required'          => true,
                'renderPassword'    => true,
                'label'             => t('Bind Password'),
                'description'       => t('The password to use for querying the ldap server')
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
        try {
            $resource = ResourceFactory::createResource(new ConfigObject($form->getValues()));
            if (false === $resource->testCredentials(
                $form->getElement('bind_dn')->getValue(),
                $form->getElement('bind_pw')->getValue()
                )
            ) {
                throw new Exception();
            }
        } catch (Exception $e) {
            $form->addError(t('Connectivity validation failed, connection to the given resource not possible.'));
            return false;
        }

        return true;
    }
}
