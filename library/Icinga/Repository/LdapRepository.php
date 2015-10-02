<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
     * Object attributes whose value is not distinguished name
     *
     * @var array
     */
    protected $ambiguousAttributes = array(
        'posixGroup' => 'memberUid'
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
     * Return whether the given object attribute's value is not a distinguished name
     *
     * @param   string  $objectClass
     * @param   string  $attributeName
     *
     * @return  bool
     */
    protected function isAmbiguous($objectClass, $attributeName)
    {
        return isset($this->ambiguousAttributes[$objectClass][$attributeName]);
    }
}
