<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Repository;

use Icinga\Protocol\Ldap\LdapConnection;

/**
 * Abstract base class for concrete LDAP repository implementations
 *
 * Additionally provided features:
 * <ul>
 *  <li>Attribute name normalization</li>
 * </ul>
 */
abstract class LdapRepository extends Repository
{
    /**
     * The datasource being used
     *
     * @var LdapConnection
     */
    protected $ds;

    /**
     * Normed attribute names based on known LDAP environments
     *
     * @var array
     */
    protected $normedAttributes = array(
        'uid'                   => 'uid',
        'gid'                   => 'gid',
        'user'                  => 'user',
        'group'                 => 'group',
        'member'                => 'member',
        'memberuid'             => 'memberUid',
        'posixgroup'            => 'posixGroup',
        'uniquemember'          => 'uniqueMember',
        'groupofnames'          => 'groupOfNames',
        'inetorgperson'         => 'inetOrgPerson',
        'samaccountname'        => 'sAMAccountName',
        'groupofuniquenames'    => 'groupOfUniqueNames'
    );

    /**
     * Create a new LDAP repository object
     *
     * @param   LdapConnection  $ds     The data source to use
     */
    public function __construct(LdapConnection $ds)
    {
        parent::__construct($ds);
    }

    /**
     * Return the given attribute name normed to known LDAP enviroments, if possible
     *
     * @param   string  $name
     *
     * @return  string
     */
    protected function getNormedAttribute($name)
    {
        $loweredName = strtolower($name);
        if (array_key_exists($loweredName, $this->normedAttributes)) {
            return $this->normedAttributes[$loweredName];
        }

        return $name;
    }

    /**
     * Return whether the given object DN is related to the given base DN
     *
     * Will use the current connection's root DN if $baseDn is not given.
     *
     * @param   string  $dn         The object DN to check
     * @param   string  $baseDn     The base DN to compare the object DN with
     *
     * @return  bool
     */
    protected function isRelatedDn($dn, $baseDn = null)
    {
        $normalizedDn = strtolower(join(',', array_map('trim', explode(',', $dn))));
        $normalizedBaseDn = strtolower(join(',', array_map('trim', explode(',', $baseDn ?: $this->ds->getDn()))));
        return strpos($normalizedDn, $normalizedBaseDn) !== false;
    }
}
