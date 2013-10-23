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
use Icinga\Filter\Type\FilterType;

/**
 * Filter attribute class representing one possible filter for a specific domain
 *
 * These classes contain a Filter Type to determine possible operators/values etc.
 * Often the filter class directly contains the attribute and handles field => attribute mapping,
 * but one exception is the BooleanFilter, which overwrites the attribute to use for a more convenient.
 *
 * Basically, this component maps multiple attributes to one specific field.
 */
class FilterAttribute extends QueryProposer
{
    /**
     * The FilterType object that handles operations on this attribute
     *
     * @var Type\FilterType
     */
    private $type;

    /**
     * An array of attribute tokens to map, or empty to let the filter type choose it's own attribute
     * and skip this class
     *
     * @var array
     */
    private $attributes = array();

    /**
     * The field that is being represented by the given attributes
     *
     * @var String
     */
    private $field;

    /**
     * Create a new FilterAttribute using the given type as the filter Type
     *
     * @param FilterType $type       The type of this filter
     */
    public function __construct(FilterType $type)
    {
        $this->type     = $type;
    }

    /**
     * Set a list of attributes to be mapped to this filter
     *
     * @param String $attr          An attribute to be recognized by this filter
     * @param String  ...
     *
     * @return self                 Fluent interface
     */
    public function setHandledAttributes($attr)
    {
        if (!$this->field) {
            $this->field = $attr;
        }
        foreach (func_get_args() as $arg) {
            $this->attributes[] = trim($arg);
        }
        return $this;
    }

    /**
     * Set the field to be represented by this FilterAttribute
     *
     * The field is always unique while the attributes are ambiguous.
     *
     * @param  String $field    The field this Attribute collection maps to
     *
     * @return self             Fluent Interface
     */
    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Return the largest attribute that matches this query or null if none matches
     *
     * @param  String $query        The query to search for containing an attribute
     *
     * @return String               The attribute to be used or null
     */
    private function getMatchingAttribute($query)
    {
        $query = trim($query);
        foreach ($this->attributes as $attribute) {
            if (stripos($query, $attribute) === 0) {
                return $attribute;
            }
        }
        return null;
    }

    /**
     * Return true if this query contains an attribute mapped by this object
     *
     * @param  String $query        The query to search for the attribute
     *
     * @return bool                 True when this query contains an attribute mapped by this filter
     */
    public function queryHasSupportedAttribute($query)
    {
        return $this->getMatchingAttribute($query) !== null;
    }

    /**
     * Return proposals for the given query part
     *
     * @param String $query     The part of the query that this specifier should parse
     *
     * @return array            An array containing 0..* proposal text tokens
     */
    public function getProposalsForQuery($query)
    {
        $query = trim($query);
        $attribute = $this->getMatchingAttribute($query);

        if ($attribute !== null || count($this->attributes) == 0) {
            $subQuery = trim(substr($query, strlen($attribute)));
            return $this->type->getProposalsForQuery($subQuery);
        } else {
            return $this->getAttributeProposalsForQuery($query);
        }
    }

    /**
     * Return an array of possible attributes that can be used for completing the query
     *
     * @param  String $query        The query to fetch completion proposals for
     *
     * @return array                An array containing 0..* strings with possible completions
     */
    public function getAttributeProposalsForQuery($query)
    {
        if ($query === '') {
            if (count($this->attributes)) {
                return array($this->attributes[0]);
            } else {
                return $this->type->getProposalsForQuery($query);
            }
        }
        $proposals = array();
        foreach ($this->attributes as $attribute) {
            if (stripos($attribute, $query) === 0) {
                $proposals[] = self::markDifference($attribute, $query);
                break;
            }
        }
        return $proposals;
    }

    /**
     * Return true if $query is a valid query for this filter, otherwise false
     *
     * @param String $query         The query to validate
     *
     * @return bool                 True if $query represents a valid filter for this object, otherwise false
     */
    public function isValidQuery($query)
    {
        $attribute = $this->getMatchingAttribute($query);
        if ($attribute === null && count($this->attributes) > 0) {
            return false;
        }
        $subQuery = trim(substr($query, strlen($attribute)));
        return $this->type->isValidQuery($subQuery);
    }

    /**
     * Convert the given query to a tree node
     *
     * @param String $query     The query to convert to a tree node
     *
     * @return Node             The tree node representing this query or null if the query is not valid
     */
    public function convertToTreeNode($query)
    {
        if (!$this->isValidQuery($query)) {
            return null;
        }
        $lValue = $this->getMatchingAttribute($query);
        $subQuery = trim(substr($query, strlen($lValue)));

        return $this->type->createTreeNode($subQuery, $this->field);
    }

    /**
     * Factory method to make filter creation more convenient, same as the constructor
     *
     * @param FilterType $type      The filtertype to use for this attribute
     *
     * @return FilterAttribute      An instance of FilterAttribute
     */
    public static function create(FilterType $type)
    {
        return new FilterAttribute($type);
    }
}
