<?php

namespace Icinga\Protocol\Ldap;
/**
 * Node class
 *
 * @package Icinga\Protocol\Ldap
 */
/**
 * This class represents an LDAP node object
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Protocol\Ldap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Node extends Root
{
    protected $connection;
    protected $rdn;
    protected $parent;

    protected function __construct(Root $parent)
    {
        $this->connection = $parent->getConnection();
        $this->parent = $parent;
    }

    public static function createWithRDN($parent, $rdn, $props = array())
    {
        $node = new Node($parent);
        $node->rdn = $rdn;
        $node->props = $props;
        return $node;
    }

    public function getRDN()
    {
        return $this->rdn;
    }

    public function getDN()
    {
        return $this->parent->getDN() . '.' . $this->getRDN();
    }
}

