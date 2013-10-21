<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Filter\Type;

use Icinga\Filter\Query\Node;
use Icinga\Filter\Type\FilterType;
use Icinga\Filter\Type\TextFilter;
use Icinga\Filter\Type\TimeRangeSpecifier;

/**
 * Filter type for monitoring states
 *
 * It's best to use the StatusFilter::createForHost and StatusFilter::createForService
 * factory methods as those correctly initialize possible states
 *
 */
class StatusFilter extends FilterType
{
    /**
     * An array containing a mapping of the textual state representation ('Ok', 'Down', etc.)
     * as the keys and the numeric value mapped by this state as the value
     *
     * @var array
     */
    private $baseStates     = array();

    /**
     * An array containing all possible textual operator tokens mapped to the
     * normalized query operator
     *
     * @var array
     */
    private $operators = array(
        'Is'        => Node::OPERATOR_EQUALS,
        '='         => Node::OPERATOR_EQUALS,
        '!='        => Node::OPERATOR_EQUALS_NOT,
        'Is Not'    => Node::OPERATOR_EQUALS_NOT
    );

    /**
     * The type of this filter ('host' or 'service')
     *
     * @var string
     */
    private $type = '';

    /**
     * The timerange subfilter that can be appended to this filter
     *
     * @var TimeRangeSpecifier
     */
    private $subFilter;

    /**
     * Create a new StatusFilter and initialize the internal state correctly.
     *
     * It's best to use the factory methods instead of new as a call to
     * setBaseStates is necessary on direct creation
     *
     */
    public function __construct()
    {
        $this->subFilter = new TimeRangeSpecifier();
    }

    /**
     * Set the type for this filter (host or service)
     *
     * @param String $type      Either 'host' or 'service'
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Create a StatusFilter instance that has been initialized for host status filters
     *
     * @return StatusFilter     The ready-to-use host status filter
     */
    public static function createForHost()
    {
        $status = new StatusFilter();
        $status->setBaseStates(
            array(
                'Up'            => 0,
                'Down'          => 1,
                'Unreachable'   => 2,
                'Pending'       => 99
            )
        );
        $status->setType('host');
        return $status;
    }

    /**
     * Create a StatusFilter instance that has been initialized for service status filters
     *
     * @return StatusFilter     The ready-to-use service status filter
     */
    public static function createForService()
    {
        $status = new StatusFilter();
        $status->setBaseStates(
            array(
                'Ok'            => 0,
                'Warning'       => 1,
                'Critical'      => 2,
                'Unknown'       => 3,
                'Pending'       => 99

            )
        );
        $status->setType('service');
        return $status;
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
        if ($query == '') {
            return $this->getOperators();
        }
        $proposals = array();
        foreach ($this->getOperators() as $operator) {
            if (stripos($operator, $query) === 0 && strlen($operator) < strlen($query)) {
                $proposals[] = self::markDifference($operator, $query);
            } elseif (stripos($query, $operator) === 0) {
                $subQuery = trim(substr($query, strlen($operator)));
                $proposals = $this->getValueProposalsForQuery($subQuery);
            }
        }
        return $proposals;
    }

    /**
     * Return an array containing all possible states
     *
     * @return array        An array containing all states mapped by this filter
     */
    private function getAllStates()
    {
        return array_keys($this->baseStates);
    }

    /**
     * Return possible tokens for completing a partial query that already contains an operator
     *
     * @param String $query         The partial query containing the operator
     *
     * @return array                An array of strings that reflect possible query completions
     */
    private function getValueProposalsForQuery($query)
    {
        if ($query == '') {
            return $this->getAllStates();
        }
        $proposals = array();

        foreach ($this->getAllStates() as $state) {
            if (self::startsWith($query, $state)) {
                $subQuery = trim(substr($query, strlen($state)));
                $proposals = array_merge($proposals, $this->subFilter->getProposalsForQuery($subQuery));
            } elseif (self::startsWith($state, $query)) {
                $proposals[] = self::markDifference($state, $query);
            }
        }
        return $proposals;
    }

    /**
     * Return an tuple containing the operator as the first, the value as the second and a possible subquery as the
     * third element by parsing the given query
     *
     * The subquery contains the time information for this status if given
     *
     * @param   String $query       The Query to parse with this filter
     *
     * @return  array               An array with three elements: array(operator, value, subQuery) or filled with nulls
     *                              if the query is not valid
     */
    private function getOperatorValueArray($query)
    {
        $result = array(null, null, null);
        $result[0] = self::getMatchingOperatorForQuery($query);
        if ($result[0] === null) {
            return $result;
        }
        $subQuery = trim(substr($query, strlen($result[0])));

        foreach ($this->getAllStates() as $state) {
            if (self::startsWith($subQuery, $state)) {
                $result[1] = $state;
            }
        }
        $result[2] = trim(substr($subQuery, strlen($result[1])));
        if ($result[2] && !$this->subFilter->isValidQuery($result[2])) {
            return array(null, null, null);
        }

        return $result;
    }

    /**
     * Return an array containing the textual presentation of all possible operators
     *
     * @return array
     */
    public function getOperators()
    {
        return array_keys($this->operators);
    }

    /**
     * Return true if the given query is a valid, complete query
     *
     * @param   String $query   The query to test for being valid and complete
     *
     * @return  bool            True when this query is valid, otherwise false
     */
    public function isValidQuery($query)
    {
        $result = $this->getOperatorValueArray($query);
        return $result[0] !== null && $result[1] !== null;
    }

    /**
     * Create a Tree Node from this filter query
     *
     * @param String $query             The query to parse and turn into a Node
     * @param String $leftOperand       The field to use for the status
     *
     * @return Node                     A node object to be added to a query tree
     */
    public function createTreeNode($query, $leftOperand)
    {
        list($operator, $valueSymbol, $timeSpec) = $this->getOperatorValueArray($query);
        if ($operator === null || $valueSymbol === null) {
            return null;
        }
        $node = Node::createOperatorNode(
            $this->operators[$operator],
            $leftOperand,
            $this->resolveValue($valueSymbol)
        );
        if ($timeSpec) {
            $left = $node;
            $node = Node::createAndNode();
            $node->left = $left;

            $node->right = $this->subFilter->createTreeNode($timeSpec, $this->type . '_last_state_change');
            $node->right->parent = $node;
            $node->left->parent = $node;
        }
        return $node;
    }

    /**
     * Return the numeric representation of state given to this filter
     *
     * @param String $valueSymbol       The state string from the query
     *
     * @return int                      The numeric state mapped by $valueSymbol or null if it's an invalid state
     */
    private function resolveValue($valueSymbol)
    {
        if (isset($this->baseStates[$valueSymbol])) {
            return $this->baseStates[$valueSymbol];
        }
        return null;
    }

    /**
     * Set possible states for this filter
     *
     * Only required when this filter isn't created by one of it's factory methods
     *
     * @param array $states     The states in an associative statename => numeric representation array
     */
    public function setBaseStates(array $states)
    {
        $this->baseStates = $states;
    }
}
