<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\UserBackend;

use Exception;
use Icinga\Authentication\User\LdapUserBackend;
use Icinga\Data\Inspection;
use Icinga\Web\Form;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\AuthenticationException;
use Icinga\Authentication\User\UserBackend;

/**
 * Form class for adding/modifying LDAP user backends
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
        $isAd = isset($formData['type']) ? $formData['type'] === 'msldap' : false;

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
                'preserveDefault'   => true,
                'required'          => ! $isAd,
                'ignore'            => $isAd,
                'disabled'          => $isAd ?: null,
                'label'             => $this->translate('LDAP User Object Class'),
                'description'       => $this->translate('The object class used for storing users on the LDAP server.'),
                'value'             => $isAd ? 'user' : 'inetOrgPerson'
            )
        );
        $this->addElement(
            'text',
            'filter',
            array(
                'preserveDefault'   => true,
                'allowEmpty'        => true,
                'value'             => $isAd ? '!(objectClass=computer)' : null,
                'label'             => $this->translate('LDAP Filter'),
                'description'       => $this->translate(
                    'An additional filter to use when looking up users using the specified connection. '
                    . 'Leave empty to not to use any additional filter rules.'
                ),
                'requirement'       => $this->translate(
                    'The filter needs to be expressed as standard LDAP expression.'
                    . ' (e.g. &(foo=bar)(bar=foo) or foo=bar)'
                ),
                'validators'        => array(
                    array(
                        'Callback',
                        false,
                        array(
                            'callback'  => function ($v) {
                                // This is not meant to be a full syntax check. It will just
                                // ensure that we can safely strip unnecessary parentheses.
                                $v = trim($v);
                                return ! $v || $v[0] !== '(' || (
                                    strpos($v, ')(') !== false ? substr($v, -2) === '))' : substr($v, -1) === ')'
                                );
                            },
                            'messages'  => array(
                                'callbackValue' => $this->translate('The filter is invalid. Please check your syntax.')
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
                'preserveDefault'   => true,
                'required'          => ! $isAd,
                'ignore'            => $isAd,
                'disabled'          => $isAd ?: null,
                'label'             => $this->translate('LDAP User Name Attribute'),
                'description'       => $this->translate(
                    'The attribute name used for storing the user name on the LDAP server.'
                ),
                'value'             => $isAd ? 'sAMAccountName' : 'uid'
            )
        );
        $this->addElement(
            'hidden',
            'backend',
            array(
                'disabled'  => true,
                'value'     => $isAd ? 'msldap' : 'ldap'
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
     * Validate that the selected resource is a valid ldap user backend
     *
     * @see Form::onSuccess()
     */
    public function onSuccess()
    {
        if (false === static::isValidUserBackend($this)) {
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
    public static function isValidUserBackend(Form $form)
    {
        /**
         * @var $result Inspection
         */
        $result = UserBackend::create(null, new ConfigObject($form->getValues()))->inspect();
        if ($result->hasError()) {
            $form->addError($result->getError());
        }

        // TODO: display diagnostics in $result->toArray() to the user

        return ! $result->hasError();
    }
}
