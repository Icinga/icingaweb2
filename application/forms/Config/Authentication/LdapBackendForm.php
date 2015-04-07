<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
     * @return  $this
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
                    'The name of this authentication provider that is used to differentiate it from others.'
                )
            )
        );
        $this->addElement(
            'select',
            'resource',
            array(
                'required'      => true,
                'label'         => $this->translate('LDAP Connection'),
                'description'   => $this->translate(
                    'The LDAP connection to use for authenticating with this provider.'
                ),
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
                'description'   => $this->translate('The object class used for storing users on the LDAP server.'),
                'value'         => 'inetOrgPerson'
            )
        );
        $this->addElement(
            'text',
            'filter',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('LDAP Filter'),
                'description'   => $this->translate(
                    'An additional filter to use when looking up users using the specified connection. '
                    . 'Leave empty to not to use any additional filter rules.'
                ),
                'requirement'   => $this->translate(
                    'The filter needs to be expressed as standard LDAP expression, without'
                    . ' outer parentheses. (e.g. &(foo=bar)(bar=foo) or foo=bar)'
                ),
                'validators'    => array(
                    array(
                        'Callback',
                        false,
                        array(
                            'callback'  => function ($v) {
                                return strpos($v, '(') !== 0;
                            },
                            'messages'  => array(
                                'callbackValue' => $this->translate('The filter must not be wrapped in parantheses.')
                            )
                        )
                    )
                )
            )
        );
        $this->addElement(
            'text',
            'user_name_attribute',
            array(
                'required'      => true,
                'label'         => $this->translate('LDAP User Name Attribute'),
                'description'   => $this->translate(
                    'The attribute name used for storing the user name on the LDAP server.'
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
                'label'         => $this->translate('LDAP Base DN'),
                'description'   => $this->translate(
                    'The path where users can be found on the LDAP server. Leave ' .
                    'empty to select all users available using the specified connection.'
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
                $form->getElement('base_dn')->getValue(),
                $form->getElement('filter')->getValue()
            );
            $ldapUserBackend->assertAuthenticationPossible();
        } catch (AuthenticationException $e) {
            if (($previous = $e->getPrevious()) !== null) {
                $form->addError($previous->getMessage());
            } else {
                $form->addError($e->getMessage());
            }

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
