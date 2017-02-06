<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

/**
 * This class represents an LDAP node object
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.com>
 * @author     Icinga-Web Team <info@icinga.com>
 * @package Icinga\Protocol\Ldap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Node extends Root
{
    /**
     * @var LdapConnection
     */
    protected $connection;

    /**
     * @var
     */
    protected $rdn;

    /**
     * @var Root
     */
    protected $parent;

    /**
     * @param Root $parent
     */
    protected function __construct(Root $parent)
    {
        $this->connection = $parent->getConnection();
        $this->parent = $parent;
    }

    /**
     * @param $parent
     * @param $rdn
     * @param array $props
     * @return Node
     */
    public static function createWithRDN($parent, $rdn, $props = array())
    {
        $node = new Node($parent);
        $node->rdn = $rdn;
        $node->props = $props;
        return $node;
    }

    /**
     * @return mixed
     */
    public function getRDN()
    {
        return $this->rdn;
    }

    /**
     * @return mixed|string
     */
    public function getDN()
    {
        return $this->getRDN() . ',' . $this->parent->getDN();
    }
}
