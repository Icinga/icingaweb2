<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

/**
 * The properties and capabilities of an LDAP server
 *
 * Provides information about the available encryption mechanisms (StartTLS), the supported
 * LDAP protocol (v2/v3), vendor-specific extensions or protocols controls and extensions.
 */
class Capability {

    const LDAP_SERVER_START_TLS_OID = '1.3.6.1.4.1.1466.20037';

    const LDAP_PAGED_RESULT_OID_STRING = '1.2.840.113556.1.4.319';

    const LDAP_SERVER_SHOW_DELETED_OID = '1.2.840.113556.1.4.417';

    const LDAP_SERVER_SORT_OID = '1.2.840.113556.1.4.473';

    const LDAP_SERVER_CROSSDOM_MOVE_TARGET_OID = '1.2.840.113556.1.4.521';

    const LDAP_SERVER_NOTIFICATION_OID = '1.2.840.113556.1.4.528';

    const LDAP_SERVER_EXTENDED_DN_OID = '1.2.840.113556.1.4.529';

    const LDAP_SERVER_LAZY_COMMIT_OID = '1.2.840.113556.1.4.619';

    const LDAP_SERVER_SD_FLAGS_OID = '1.2.840.113556.1.4.801';

    const LDAP_SERVER_TREE_DELETE_OID = '1.2.840.113556.1.4.805';

    const LDAP_SERVER_DIRSYNC_OID = '1.2.840.113556.1.4.841';

    const LDAP_SERVER_VERIFY_NAME_OID = '1.2.840.113556.1.4.1338';

    const LDAP_SERVER_DOMAIN_SCOPE_OID = '1.2.840.113556.1.4.1339';

    const LDAP_SERVER_SEARCH_OPTIONS_OID = '1.2.840.113556.1.4.1340';

    const LDAP_SERVER_PERMISSIVE_MODIFY_OID = '1.2.840.113556.1.4.1413';

    const LDAP_SERVER_ASQ_OID = '1.2.840.113556.1.4.1504';

    const LDAP_SERVER_FAST_BIND_OID = '1.2.840.113556.1.4.1781';

    const LDAP_CONTROL_VLVREQUEST = '2.16.840.1.113730.3.4.9';


    // MS Capabilities, Source: http://msdn.microsoft.com/en-us/library/cc223359.aspx

    // Running Active Directory as AD DS
    const LDAP_CAP_ACTIVE_DIRECTORY_OID = '1.2.840.113556.1.4.800';

    // Capable of signing and sealing on an NTLM authenticated connection
    // and of performing subsequent binds on a signed or sealed connection
    const LDAP_CAP_ACTIVE_DIRECTORY_LDAP_INTEG_OID = '1.2.840.113556.1.4.1791';

    // If AD DS: running at least W2K3, if AD LDS running at least W2K8
    const LDAP_CAP_ACTIVE_DIRECTORY_V51_OID = '1.2.840.113556.1.4.1670';

    // If AD LDS: accepts DIGEST-MD5 binds for AD LDSsecurity principals
    const LDAP_CAP_ACTIVE_DIRECTORY_ADAM_DIGEST  = '1.2.840.113556.1.4.1880';

    // Running Active Directory as AD LDS
    const LDAP_CAP_ACTIVE_DIRECTORY_ADAM_OID = '1.2.840.113556.1.4.1851';

    // If AD DS: it's a Read Only DC (RODC)
    const LDAP_CAP_ACTIVE_DIRECTORY_PARTIAL_SECRETS_OID = '1.2.840.113556.1.4.1920';

    // Running at least W2K8
    const LDAP_CAP_ACTIVE_DIRECTORY_V60_OID = '1.2.840.113556.1.4.1935';

    // Running at least W2K8r2
    const LDAP_CAP_ACTIVE_DIRECTORY_V61_R2_OID = '1.2.840.113556.1.4.2080';

    // Running at least W2K12
    const LDAP_CAP_ACTIVE_DIRECTORY_W8_OID = '1.2.840.113556.1.4.2237';

    /**
     * Attributes of the LDAP Server returned by the discovery query
     *
     * @var StdClass
     */
    private $attributes;

    /**
     * Map of supported available OIDS
     *
     * @var array
     */
    private $oids = array();

    /**
     * Construct a new capability
     *
     * @param $attributes   StdClass    The attributes returned, may be null for guessing default capabilities
     */
    public function __construct($attributes = null)
    {
        $this->attributes = $attributes;

        $keys = array('supportedControl', 'supportedExtension', 'supportedFeatures', 'supportedCapabilities');
        foreach ($keys as $key) {
            if (isset($attributes->$key)) {
                if (is_array($attributes->$key)) {
                    foreach ($attributes->$key as $oid) {
                        $this->oids[$oid] = true;
                    }
                } else {
                    $this->oids[$attributes->$key] = true;
                }
            }
        }
    }

    /**
     * Return if the capability object contains support for StartTLS
     *
     * @return      bool    Whether StartTLS is supported
     */
    public function hasStartTls()
    {
        return isset($this->oids[self::LDAP_SERVER_START_TLS_OID]);
    }

    /**
     * Return if the capability object contains support for StartTLS
     *
     * @return      bool Whether StartTLS is supported
     */
    public function hasPagedResult()
    {
        return isset($this->oids[self::LDAP_PAGED_RESULT_OID_STRING]);
    }

    /**
     * Whether the ldap server is an ActiveDirectory server
     *
     * @return      boolean
     */
    public function hasAdOid()
    {
        return isset($this->oids[self::LDAP_CAP_ACTIVE_DIRECTORY_OID]);
    }

    /**
     * Return if the capability objects contains support for LdapV3, defaults to true if discovery failed
     *
     * @return bool
     */
    public function hasLdapV3()
    {
        if (! isset($this->attributes) || ! isset($this->attributes->supportedLDAPVersion)) {
            // Default to true, if unknown
            return true;
        }

        return (is_string($this->attributes->supportedLDAPVersion)
            && (int) $this->attributes->supportedLDAPVersion === 3)
        || (is_array($this->attributes->supportedLDAPVersion)
            && in_array(3, $this->attributes->supportedLDAPVersion));
    }

    /**
     * Whether the capability with the given OID is supported
     *
     * @param $oid  string  The OID of the capability
     *
     * @return      bool
     */
    public function hasOid($oid)
    {
        return isset($this->oids[$oid]);
    }

    /**
     * Get the default naming context
     *
     * @return string|null the default naming context, or null when no contexts are available
     */
    public function getDefaultNamingContext()
    {
        // defaultNamingContext entry has higher priority
        if (isset($this->attributes->defaultNamingContext)) {
            return $this->attributes->defaultNamingContext;
        }

        // if its missing use namingContext
        $namingContexts = $this->namingContexts();
        return empty($namingContexts) ? null : $namingContexts[0];
    }

    /**
     * Fetch the namingContexts
     *
     * @return array    the available naming contexts
     */
    public function namingContexts()
    {
        if (!isset($this->attributes->namingContexts)) {
            return array();
        }
        if (!is_array($this->attributes->namingContexts)) {
            return array($this->attributes->namingContexts);
        }
        return$this->attributes->namingContexts;
    }
}
