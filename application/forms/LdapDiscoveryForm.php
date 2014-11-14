<?php

namespace Icinga\Form;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Protocol\Ldap\Exception as LdapException;
use Icinga\Protocol\Ldap\Connection;
use Icinga\Protocol\Dns;
use Icinga\Web\Form;

/**
 * Form class for application-wide and logging specific settings
 */
class LdapDiscoveryForm extends Form
{
    /**
     * The discovered server settings
     *
     * @var array
     */
    private $capabilities = null;

    /**
     * The discovered root_dn
     *
     * @var null
     */
    private $namingContext = null;

    /**
     * The working domain name
     *
     * @var null
     */
    private $domain = null;

    /**
     * The working port name
     *
     * @var int
     */
    private $port = 389;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('form_ldap_discovery');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'domain',
            array(
                'required'      => true,
                'label'         => t('Search Domain'),
                'description'   => t('Search this domain for records of available servers.'),
            )
        );

        if (false) {
            $this->addElement(
                'note',
                'additional_description',
                array(
                    'value' => t('No Ldap servers found on this domain.'
                        . ' You can try to specify host and port and try again, or just skip this step and '
                        . 'configure the server manually.'
                    )
                )
            );
            $this->addElement(
                'text',
                'hostname',
                array(
                    'required'      => false,
                    'label'         => t('Host'),
                    'description'   => t('IP or host name to search.'),
                )
            );

            $this->addElement(
                'text',
                'port',
                array(
                    'required'      => false,
                    'label'         => t('Port'),
                    'description'   => t('Port', 389),
                )
            );
        }
        return $this;
    }

    public function isValid($data)
    {
        if (false === parent::isValid($data)) {
            return false;
        }
        if ($this->discover($this->getValue('domain'))) {
            return true;
        }
        return true;
    }


    private function discover($domain)
    {
        // Attempt 1: Connect to the domain directly
        if ($this->discoverCapabilities(array(
                'hostname' => $domain,
                'port'     => 389)
        )) {
            return true;
        }

        // Attempt 2: Discover all available ldap dns records and connect to the first one
        $cap = false;
        $records = array_merge(Dns::getSrvRecords($domain, 'ldap'), Dns::getSrvRecords($domain, 'ldaps'));
        if (isset($records[0])) {
            $record = $records[0];
            if (isset($record['port'])) {
                $cap = $this->discoverCapabilities(array(
                    'hostname' => $record['target'],
                    'port'     => $record['port']
                ));
            } else {
                $cap = $this->discoverCapabilities(array(
                    'hostname' => $record['target'],
                    'port'     => 389
                ));
            }
        }
        return $cap;
    }

    private function discoverCapabilities($config)
    {
        $conn = new Connection(new Config($config));
        try {
            $conn->connect();
            $this->capabilities = $conn->getCapabilities();
            $this->namingContext = $conn->getDefaultNamingContext();
            $this->port = $config['port'];
            $this->domain = $config['hostname'];
            return true;
        } catch (LdapException $e) {
            Logger::info(
                'Ldap discovery for ' . $config['hostname'] . ':' . $config['port'] . ' failed: ' . $e->getMessage()
            );
            return false;
        }
     }

    public function suggestResourceSettings()
    {
        if (! isset($this->capabilities)) {
            return array();
        }
        if ($this->capabilities->msCapabilities->ActiveDirectoryOid) {
            return array(
                'hostname' => $this->domain,
                'port' => $this->port,
                'root_dn' => $this->namingContext
            );
        } else {
            return array(
                'hostname' => $this->domain,
                'port' => $this->port,
                'root_dn' => $this->namingContext
            );
        }
    }

    public function hasSuggestion()
    {
        return isset($this->capabilities);
    }

    public function suggestBackendSettings()
    {
        if (! isset($this->capabilities)) {
            return array();
        }
        if ($this->capabilities->msCapabilities->ActiveDirectoryOid) {
            return array(
                'base_dn' => $this->namingContext,
                'user_class' => 'user',
                'user_name_attribute' => 'sAMAccountName'
            );
        } else {
            return array(
                'base_dn' => $this->namingContext,
                'user_class' => 'inetOrgPerson',
                'user_name_attribute' => 'uid'
            );
        }
    }

    public function isAd()
    {
        return $this->capabilities->msCapabilities->ActiveDirectoryOid;
    }
}