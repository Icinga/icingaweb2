<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Ldap;

/**
 * This class represents an LDAP node object
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package Icinga\Protocol\Ldap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Node extends Root
{
    /**
     * @var Connection
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
        return $this->parent->getDN() . '.' . $this->getRDN();
    }
}
