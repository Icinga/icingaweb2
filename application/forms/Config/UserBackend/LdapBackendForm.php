<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\UserBackend;

use Exception;
use Icinga\Data\ResourceFactory;
use Icinga\Protocol\Ldap\LdapCapabilities;
use Icinga\Protocol\Ldap\LdapConnection;
use Icinga\Protocol\Ldap\LdapException;
use Icinga\Web\Form;

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
     * Default values for the form elements
     *
     * @var string[]
     */
    protected $suggestions = array();

    /**
     * Cache for {@link getLdapCapabilities()}
     *
     * @var LdapCapabilities
     */
    protected $ldapCapabilities;

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
     * Create and add elements to this form
     *
     * @param   array   $formData
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
                ),
                'value'         => $this->getSuggestion('name')
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
                'multiOptions'  => !empty($this->resources)
                    ? array_combine($this->resources, $this->resources)
                    : array(),
                'value'         => $this->getSuggestion('resource')
            )
        );

        if (! $isAd && !empty($this->resources)) {
            $this->addElement(
                'button',
                'discovery_btn',
                array(
                    'class'             => 'control-button',
                    'type'              => 'submit',
                    'value'             => 'discovery_btn',
                    'label'             => $this->translate('Discover', 'A button to discover LDAP capabilities'),
                    'title'             => $this->translate(
                        'Push to fill in the chosen connection\'s default settings.'
                    ),
                    'decorators'        => array(
                        array('ViewHelper', array('separator' => '')),
                        array('Spinner'),
                        array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                    ),
                    'formnovalidate'    => 'formnovalidate'
                )
            );
        }

        if ($isAd) {
            // ActiveDirectory defaults
            $userClass = 'user';
            $filter = '!(objectClass=computer)';
            $userNameAttribute = 'sAMAccountName';
        } else {
            // OpenLDAP defaults
            $userClass = 'inetOrgPerson';
            $filter = null;
            $userNameAttribute = 'uid';
        }

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
                'value'             => $this->getSuggestion('user_class', $userClass)
            )
        );
        $this->addElement(
            'text',
            'filter',
            array(
                'preserveDefault'   => true,
                'allowEmpty'        => true,
                'value'             => $this->getSuggestion('filter', $filter),
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
                'value'             => $this->getSuggestion('user_name_attribute', $userNameAttribute)
            )
        );
        $this->addElement(
            'hidden',
            'backend',
            array(
                'disabled'  => true,
                'value'     => $this->getSuggestion('backend', $isAd ? 'msldap' : 'ldap')
            )
        );
        $this->addElement(
            'text',
            'base_dn',
            array(
                'preserveDefault'   => true,
                'required'          => false,
                'label'             => $this->translate('LDAP Base DN'),
                'description'       => $this->translate(
                    'The path where users can be found on the LDAP server. Leave ' .
                    'empty to select all users available using the specified connection.'
                ),
                'value'             => $this->getSuggestion('base_dn')
            )
        );

        $this->addElement(
            'text',
            'domain',
            array(
                'label'         => $this->translate('Domain'),
                'description'   => $this->translate(
                    'The domain the LDAP server is responsible for upon authentication.'
                    . ' Note that if you specify a domain here,'
                    . ' the LDAP backend only authenticates users who specify a domain upon login.'
                    . ' If the domain of the user matches the domain configured here, this backend is responsible for'
                    . ' authenticating the user based on the username without the domain part.'
                    . ' If your LDAP backend holds usernames with a domain part or if it is not necessary in your setup'
                    . ' to authenticate users based on their domains, leave this field empty.'
                ),
                'preserveDefault' => true,
                'value'         => $this->getSuggestion('domain')
            )
        );

        $this->addElement(
            'button',
            'btn_discover_domain',
            array(
                'class'             => 'control-button',
                'type'              => 'submit',
                'value'             => 'discovery_btn',
                'label'             => $this->translate('Discover the domain'),
                'title'             => $this->translate(
                    'Push to disover and fill in the domain of the LDAP server.'
                ),
                'decorators'        => array(
                    array('ViewHelper', array('separator' => '')),
                    array('Spinner'),
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                ),
                'formnovalidate'    => 'formnovalidate'
            )
        );
    }

    public function isValidPartial(array $formData)
    {
        $isAd = isset($formData['type']) && $formData['type'] === 'msldap';
        $baseDn = null;
        $hasAdOid = false;
        $discoverySuccessful = false;

        if (! $isAd && ! empty($this->resources) && isset($formData['discovery_btn'])
            && $formData['discovery_btn'] === 'discovery_btn') {
            $discoverySuccessful = true;
            try {
                $capabilities = $this->getLdapCapabilities($formData);
                $baseDn = $capabilities->getDefaultNamingContext();
                $hasAdOid = $capabilities->isActiveDirectory();
            } catch (Exception $e) {
                $this->warning(sprintf(
                    $this->translate('Failed to discover the chosen LDAP connection: %s'),
                    $e->getMessage()
                ));
                $discoverySuccessful = false;
            }
        }

        if ($discoverySuccessful) {
            if ($isAd || $hasAdOid) {
                // ActiveDirectory defaults
                $userClass = 'user';
                $filter = '!(objectClass=computer)';
                $userNameAttribute = 'sAMAccountName';
            } else {
                // OpenLDAP defaults
                $userClass = 'inetOrgPerson';
                $filter = null;
                $userNameAttribute = 'uid';
            }

            $formData['user_class'] = $userClass;

            if (! isset($formData['filter']) || $formData['filter'] === '') {
                $formData['filter'] = $filter;
            }

            $formData['user_name_attribute'] = $userNameAttribute;

            if ($baseDn !== null && (! isset($formData['base_dn']) || $formData['base_dn'] === '')) {
                $formData['base_dn'] = $baseDn;
            }
        }

        if (isset($formData['btn_discover_domain']) && $formData['btn_discover_domain'] === 'discovery_btn') {
            try {
                $formData['domain'] = $this->discoverDomain($formData);
            } catch (LdapException $e) {
                $this->error($e->getMessage());
            }
        }

        return parent::isValidPartial($formData);
    }

    /**
     * Get the LDAP capabilities of either the resource specified by the user or the default one
     *
     * @param   string[]    $formData
     *
     * @return  LdapCapabilities
     */
    protected function getLdapCapabilities(array $formData)
    {
        if ($this->ldapCapabilities === null) {
            $this->ldapCapabilities = ResourceFactory::create(
                isset($formData['resource']) ? $formData['resource'] : reset($this->resources)
            )->bind()->getCapabilities();
        }

        return $this->ldapCapabilities;
    }

    /**
     * Discover the domain the LDAP server is responsible for
     *
     * @param   string[]    $formData
     *
     * @return  string
     */
    protected function discoverDomain(array $formData)
    {
        $cap = $this->getLdapCapabilities($formData);

        if ($cap->isActiveDirectory()) {
            $netBiosName = $cap->getNetBiosName();
            if ($netBiosName !== null) {
                return $netBiosName;
            }
        }

        return $this->defaultNamingContextToFQDN($cap);
    }

    /**
     * Get the default naming context as FQDN
     *
     * @param   LdapCapabilities    $cap
     *
     * @return  string|null
     */
    protected function defaultNamingContextToFQDN(LdapCapabilities $cap)
    {
        $defaultNamingContext = $cap->getDefaultNamingContext();
        if ($defaultNamingContext !== null) {
            $validationMatches = array();
            if (preg_match('/\bdc=[^,]+(?:,dc=[^,]+)*$/', strtolower($defaultNamingContext), $validationMatches)) {
                $splitMatches = array();
                preg_match_all('/dc=([^,]+)/', $validationMatches[0], $splitMatches);
                return implode('.', $splitMatches[1]);
            }
        }
    }

    /**
     * Get the default values for the form elements
     *
     * @return string[]
     */
    public function getSuggestions()
    {
        return $this->suggestions;
    }

    /**
     * Get the default value for the given form element or the given default
     *
     * @param   string  $element
     * @param   string  $default
     *
     * @return  string
     */
    public function getSuggestion($element, $default = null)
    {
        return isset($this->suggestions[$element]) ? $this->suggestions[$element] : $default;
    }

    /**
     * Set the default values for the form elements
     *
     * @param string[] $suggestions
     *
     * @return $this
     */
    public function setSuggestions(array $suggestions)
    {
        $this->suggestions = $suggestions;

        return $this;
    }
}
