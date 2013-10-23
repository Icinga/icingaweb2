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

namespace Icinga\Filter;

use Icinga\Filter\Query\Node;

/**
 * A Filter domain represents an object that supports filter operations and is basically a
 * container for filter attribute
 *
 */
class Domain extends QueryProposer
{
    /**
     * The label to filter for
     *
     * @var string
     */
    private $label;

    /**
     * @var array
     */
    private $attributes = array();

    /**
     * Create a new domain identified by the given label
     *
     * @param $label
     */
    public function __construct($label)
    {
        $this->label = trim($label);
    }

    /**
     * Return true when this domain handles a given query (even if it's incomplete)
     *
     * @param  String $query        The query to test this domain with
     * @return bool                 True if this domain can handle the query
     */
    public function handlesQuery($query)
    {
        $query = trim($query);
        return stripos($query, $this->label) === 0;
    }

    /**
     * Register an attribute to be handled for this filter domain
     *
     * @param FilterAttribute $attr         The attribute object to add to the filter
     * @return self                         Fluent interface
     */
    public function registerAttribute(FilterAttribute $attr)
    {
        $this->attributes[] = $attr;
        return $this;
    }

    /**
     * Return proposals for the given query part
     *
     * @param String $query    The part of the query that this specifier should parse
     * @return array            An array containing 0..* proposal text tokens
     */
    public function getProposalsForQuery($query)
    {
        $query = trim($query);
        if ($this->handlesQuery($query)) {
            // remove domain portion of the query
            $query = trim(substr($query, strlen($this->label)));
        }

        $proposals = array();
        foreach ($this->attributes as $attributeHandler) {
            $proposals = array_merge($proposals, $attributeHandler->getProposalsForQuery($query));
        }

        return $proposals;
    }

    /**
     * Return the label identifying this domain
     *
     * @return string       the label for this domain
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Create a query tree node representing the given query and using the field given as
     * $leftOperand as the attribute (left leaf of the tree)
     *
     * @param String $query             The query to create the node from
     * @param String $leftOperand       The attribute use for the node
     * @return Node|null
     */
    public function convertToTreeNode($query)
    {
        if ($this->handlesQuery($query)) {
            // remove domain portion of the query
            $query = trim(substr($query, strlen($this->label)));
        }

        foreach ($this->attributes as $attributeHandler) {
            if ($attributeHandler->isValidQuery($query)) {
                $node = $attributeHandler->convertToTreeNode($query);
                return $node;
            }
        }
        return null;
    }
}
