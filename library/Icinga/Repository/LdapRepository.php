<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Repository;

use Icinga\Protocol\Ldap\Connection;

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
     * @var Connection
     */
    protected $ds;

    /**
     * Normed attribute names based on known LDAP environments
     *
     * @var array
     */
    protected $normedAttributes = array(
        'uid'               => 'uid',
        'gid'               => 'gid',
        'user'              => 'user',
        'group'             => 'group',
        'member'            => 'member',
        'inetorgperson'     => 'inetOrgPerson',
        'samaccountname'    => 'sAMAccountName'
    );

    /**
     * Create a new LDAP repository object
     *
     * @param   Connection  $ds     The data source to use
     */
    public function __construct(Connection $ds)
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
}