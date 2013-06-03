<?php

namespace Icinga\Protocol\Ldap;
/**
 * Root class
 *
 * @package Icinga\Protocol\Ldap
 */
/**
 * This class is a special node object, representing your connections root node
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Protocol\Ldap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Root
{
    protected $rdn;
    protected $connection;
    protected $children = array();
    protected $props = array();

    protected function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function hasParent()
    {
        return false;
    }

    public static function forConnection(Connection $connection)
    {
        $root = new Root($connection);
        return $root;
    }

    public function createChildByDN($dn, $props = array())
    {
        $dn = $this->stripMyDN($dn);
        $parts = array_reverse(LdapUtils::explodeDN($dn));
        $parent = $this;
        while($rdn = array_shift($parts)) {
            if ($parent->hasChildRDN($rdn)) {
                $child = $parent->getChildByRDN($rdn);
            } else {
                $child = Node::createWithRDN($parent, $rdn, (array) $props);
                $parent->addChild($child);
            }
            $parent = $child;
        }
        return $child;
    }

    public function hasChildRDN($rdn)
    {
        return array_key_exists(strtolower($rdn), $this->children);
    }

    public function getChildByRDN($rdn)
    {
        if (! $this->hasChildRDN($rdn)) {
            throw new Exception(sprintf(
                'The child RDN "%s" is not available',
                $rdn
            ));
        }
        return $this->children[strtolower($rdn)];
    }

    public function children()
    {
        return $this->children;
    }

    public function hasChildren()
    {
        return ! empty($this->children);
    }

    public function addChild(Node $child)
    {
        $this->children[strtolower($child->getRDN())] = $child;
        return $this;
    }

    protected function stripMyDN($dn)
    {
        $this->assertSubDN($dn);
        return substr($dn, 0, strlen($dn) - strlen($this->getDN()) - 1);
    }

    protected function assertSubDN($dn)
    {
        $mydn = $this->getDN();
        $end = substr($dn, -1 * strlen($mydn));
        if (strtolower($end) !== strtolower($mydn)) {
            throw new Exception(sprintf(
                '"%s" is not a child of "%s"',
                $dn,
                $mydn
            ));
        }
        if (strlen($dn) === strlen($mydn)) {
            throw new Exception(sprintf(
                '"%s" is not a child of "%s", they are equal',
                $dn,
                $mydn
            ));
        }
        return $this;
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function hasBeenChanged()
    {
        return false;
    }

    public function getRDN()
    {
        return $this->getDN();
    }

    public function getDN()
    {
        return $this->connection->getDN();
    }

    public function __get($key)
    {
        if (! array_key_exists($key, $this->props)) {
            return null;
        }
        return $this->props[$key];
    }

    public function __isset($key)
    {
        return array_key_exists($key, $this->props);
    }
}

