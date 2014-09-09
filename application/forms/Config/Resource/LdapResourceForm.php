<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Resource;

use Exception;
use Zend_Config;
use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Web\Form\Element\Number;
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
            'hostname',
            array(
                'required'      => true,
                'label'         => t('Host'),
                'description'   => t('The hostname or address of the LDAP server to use for authentication'),
                'value'         => 'localhost'
            )
        );
        $this->addElement(
            new Number(
                array(
                    'required'      => true,
                    'name'          => 'port',
                    'label'         => t('Port'),
                    'description'   => t('The port of the LDAP server to use for authentication'),
                    'value'         => 389
                )
            )
        );
        $this->addElement(
            'text',
            'root_dn',
            array(
                'required'      => true,
                'label'         => t('Root DN'),
                'description'   => t('The path where users can be found on the ldap server')
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
    public function onSuccess(Request $request)
    {
        if (false === $this->isValidResource($this)) {
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
    public function isValidResource(Form $form)
    {
        try {
            $resource = ResourceFactory::createResource(new Zend_Config($form->getValues()));
            $resource->connect();
        } catch (Exception $e) {
            $form->addError(t('Connectivity validation failed, connection to the given resource not possible.'));
            return false;
        }

        return true;
    }
}
