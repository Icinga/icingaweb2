<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Forms\Config\Authentication;

use Exception;
use Icinga\Web\Form;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\AuthenticationException;
use Icinga\Authentication\Backend\LdapUserBackend;

/**
 * Form class for adding/modifying LDAP authentication backends
 */
class LdapBackendForm extends Form
{
    /**
     * The ldap resource names the user can choose from
     *
     * @var array
     */
    protected $resources;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_authbackend_ldap');
    }

    /**
     * Set the resource names the user can choose from
     *
     * @param   array   $resources      The resources to choose from
     *
     * @return  self
     */
    public function setResources(array $resources)
    {
        $this->resources = $resources;
        return $this;
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
                'label'         => $this->translate('Backend Name'),
                'description'   => $this->translate(
                    'The name of this authentication provider that is used to differentiate it from others'
                )
            )
        );
        $this->addElement(
            'select',
            'resource',
            array(
                'required'      => true,
                'label'         => $this->translate('LDAP Resource'),
                'description'   => $this->translate('The resource to use for authenticating with this provider'),
                'multiOptions'  => false === empty($this->resources)
                    ? array_combine($this->resources, $this->resources)
                    : array()
            )
        );
        $this->addElement(
            'text',
            'user_class',
            array(
                'required'      => true,
                'label'         => $this->translate('LDAP User Object Class'),
                'description'   => $this->translate('The object class used for storing users on the ldap server'),
                'value'         => 'inetOrgPerson'
            )
        );
        $this->addElement(
            'text',
            'user_name_attribute',
            array(
                'required'      => true,
                'label'         => $this->translate('LDAP User Name Attribute'),
                'description'   => $this->translate(
                    'The attribute name used for storing the user name on the ldap server'
                ),
                'value'         => 'uid'
            )
        );
        $this->addElement(
            'hidden',
            'backend',
            array(
                'disabled'  => true,
                'value'     => 'ldap'
            )
        );
        $this->addElement(
            'text',
            'base_dn',
            array(
                'required'      => false,
                'label'         => $this->translate('Base DN'),
                'description'   => $this->translate(
                    'The path where users can be found on the ldap server. Leave ' .
                    'empty to select all users available on the specified resource.'
                )
            )
        );
        return $this;
    }

    /**
     * Validate that the selected resource is a valid ldap authentication backend
     *
     * @see Form::onSuccess()
     */
    public function onSuccess()
    {
        if (false === static::isValidAuthenticationBackend($this)) {
            return false;
        }
    }

    /**
     * Validate the configuration by creating a backend and requesting the user count
     *
     * @param   Form    $form   The form to fetch the configuration values from
     *
     * @return  bool            Whether validation succeeded or not
     */
    public static function isValidAuthenticationBackend(Form $form)
    {
        try {
            $ldapUserBackend = new LdapUserBackend(
                ResourceFactory::createResource($form->getResourceConfig()),
                $form->getElement('user_class')->getValue(),
                $form->getElement('user_name_attribute')->getValue(),
                $form->getElement('base_dn')->getValue()
            );
            $ldapUserBackend->assertAuthenticationPossible();
        } catch (AuthenticationException $e) {
            $form->addError($e->getMessage());
            return false;
        } catch (Exception $e) {
            $form->addError(sprintf($form->translate('Unable to validate authentication: %s'), $e->getMessage()));
            return false;
        }

        return true;
    }

    /**
     * Return the configuration for the chosen resource
     *
     * @return  ConfigObject
     */
    public function getResourceConfig()
    {
        return ResourceFactory::getResourceConfig($this->getValue('resource'));
    }
}
