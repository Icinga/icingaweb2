<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Doc;

use LogicException;
use Icinga\Data\Identifiable;
use Icinga\Data\Tree\Node;

/**
 * Documentation tree
 */
class DocTree extends Node
{
    /**
     * All nodes of the tree
     *
     * @var array
     */
    protected $nodes = array();

    /**
     * Append a root node to the tree
     *
     * @param Identifiable $root
     */
    public function addRoot(Identifiable $root)
    {
        $rootId = $root->getId();
        if (isset($this->nodes[$rootId])) {
            $rootId = uniqid($rootId);
//            throw new LogicException(
//                sprintf('Can\'t add root node: a root node with the id \'%s\' already exists', $rootId)
//            );
        }
        $this->nodes[$rootId] = $this->appendChild($root);
    }

    /**
     * Append a child node to a parent node
     *
     * @param   Identifiable $child
     * @param   Identifiable $parent
     *
     * @throws  LogicException If the the tree does not contain the parent node
     */
    public function addChild(Identifiable $child, Identifiable $parent)
    {
        $childId = $child->getId();
        $parentId = $parent->getId();
        if (isset($this->nodes[$childId])) {
            $childId = uniqid($childId);
//            throw new LogicException(
//                sprintf('Can\'t add child node: a child node with the id \'%s\' already exists', $childId)
//            );
        }
        if (! isset($this->nodes[$parentId])) {
            throw new LogicException(
                sprintf(mt('doc', 'Can\'t add child node: there\'s no parent node having the id \'%s\''), $parentId)
            );
        }
        $this->nodes[$childId] = $this->nodes[$parentId]->appendChild($child);
    }

    /**
     * Get a node
     *
     * @param   mixed $id
     *
     * @return  Node|null
     */
    public function getNode($id)
    {
        if (! isset($this->nodes[$id])) {
            return null;
        }
        return $this->nodes[$id];
    }
}
