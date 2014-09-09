<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use Exception;
use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Data\ResourceFactory;
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
                'label'         => t('Backend Name'),
                'description'   => t('The name of this authentication backend')
            )
        );
        $this->addElement(
            'select',
            'resource',
            array(
                'required'      => true,
                'label'         => t('LDAP Resource'),
                'description'   => t('The resource to use for authenticating with this provider'),
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
                'label'         => t('LDAP User Object Class'),
                'description'   => t('The object class used for storing users on the ldap server'),
                'value'         => 'inetOrgPerson'
            )
        );
        $this->addElement(
            'text',
            'user_name_attribute',
            array(
                'required'      => true,
                'label'         => t('LDAP User Name Attribute'),
                'description'   => t('The attribute name used for storing the user name on the ldap server'),
                'value'         => 'uid'
            )
        );
        $this->addElement(
            'hidden',
            'backend',
            array(
                'required'  => true,
                'value'     => 'ldap'
            )
        );

        return $this;
    }

    /**
     * Validate that the selected resource is a valid ldap authentication backend
     *
     * @see Form::onSuccess()
     */
    public function onSuccess(Request $request)
    {
        if (false === $this->isValidAuthenticationBackend($this)) {
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
    public function isValidAuthenticationBackend(Form $form)
    {
        $element = $form->getElement('resource');

        try {
            $ldapUserBackend = new LdapUserBackend(
                ResourceFactory::create($element->getValue()),
                $form->getElement('user_class')->getValue(),
                $form->getElement('user_name_attribute')->getValue()
            );
            $ldapUserBackend->assertAuthenticationPossible();
        } catch (Exception $e) {
            $element->addError(sprintf(t('Connection validation failed: %s'), $e->getMessage()));
            return false;
        }

        return true;
    }
}
