<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Ldap;

/**
 * This class is a special node object, representing your connections root node
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Protocol\Ldap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @package Icinga\Protocol\Ldap
 */
class Root
{
    /**
     * @var string
     */
    protected $rdn;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $children = array();

    /**
     * @var array
     */
    protected $props = array();

    /**
     * @param Connection $connection
     */
    protected function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return false;
    }

    /**
     * @param Connection $connection
     * @return Root
     */
    public static function forConnection(Connection $connection)
    {
        $root = new Root($connection);
        return $root;
    }

    /**
     * @param $dn
     * @param array $props
     * @return Node
     */
    public function createChildByDN($dn, $props = array())
    {
        $dn = $this->stripMyDN($dn);
        $parts = array_reverse(LdapUtils::explodeDN($dn));
        $parent = $this;
        while ($rdn = array_shift($parts)) {
            if ($parent->hasChildRDN($rdn)) {
                $child = $parent->getChildByRDN($rdn);
            } else {
                $child = Node::createWithRDN($parent, $rdn, (array)$props);
                $parent->addChild($child);
            }
            $parent = $child;
        }
        return $child;
    }

    /**
     * @param $rdn
     * @return bool
     */
    public function hasChildRDN($rdn)
    {
        return array_key_exists(strtolower($rdn), $this->children);
    }

    /**
     * @param $rdn
     * @return mixed
     * @throws Exception
     */
    public function getChildByRDN($rdn)
    {
        if (!$this->hasChildRDN($rdn)) {
            throw new Exception(
                sprintf(
                    'The child RDN "%s" is not available',
                    $rdn
                )
            );
        }
        return $this->children[strtolower($rdn)];
    }

    /**
     * @return array
     */
    public function children()
    {
        return $this->children;
    }

    public function countChildren()
    {
        return count($this->children);
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }

    /**
     * @param Node $child
     * @return $this
     */
    public function addChild(Node $child)
    {
        $this->children[strtolower($child->getRDN())] = $child;
        return $this;
    }

    /**
     * @param $dn
     * @return string
     */
    protected function stripMyDN($dn)
    {
        $this->assertSubDN($dn);
        return substr($dn, 0, strlen($dn) - strlen($this->getDN()) - 1);
    }

    /**
     * @param $dn
     * @return $this
     * @throws Exception
     */
    protected function assertSubDN($dn)
    {
        $mydn = $this->getDN();
        $end = substr($dn, -1 * strlen($mydn));
        if (strtolower($end) !== strtolower($mydn)) {
            throw new Exception(
                sprintf(
                    '"%s" is not a child of "%s"',
                    $dn,
                    $mydn
                )
            );
        }
        if (strlen($dn) === strlen($mydn)) {
            throw new Exception(
                sprintf(
                    '"%s" is not a child of "%s", they are equal',
                    $dn,
                    $mydn
                )
            );
        }
        return $this;
    }

    /**
     * @param Connection $connection
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return bool
     */
    public function hasBeenChanged()
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function getRDN()
    {
        return $this->getDN();
    }

    /**
     * @return mixed
     */
    public function getDN()
    {
        return $this->connection->getDN();
    }

    /**
     * @param $key
     * @return null
     */
    public function __get($key)
    {
        if (!array_key_exists($key, $this->props)) {
            return null;
        }
        return $this->props[$key];
    }

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key)
    {
        return array_key_exists($key, $this->props);
    }
}
