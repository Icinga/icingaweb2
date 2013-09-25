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

namespace Icinga\Filter;


use Icinga\Filter\Query\Tree;
use Icinga\Filter\Query\Node;

/**
 * Class for filter input and query parsing
 *
 * This class handles the top level parsing of queries, i.e.
 * - Splitting queries at conjunctions and parsing them part by part
 * - Delegating the query parts to specific filter domains handling this filters
 * - Building a query tree that allows to convert a filter representation into others (url to string, string to url, sql..)
 *
 * Filters are split in Filter Domains, Attributes and Types:
 *
 *       Attribute
 * Domain  |    FilterType
 *  _|__  _|_  ______|____
 * /    \/   \/           \
 *  Host name is not 'test'
 *
 */
class Filter extends QueryProposer
{
    /**
     * The default domain to use, if not set the first added domain
     *
     * @var null
     */
    private $defaultDomain = null;

    /**
     * An array containing all query parts that couldn't be parsed
     *
     * @var array
     */
    private $ignoredQueryParts = array();

    /**
     * An array containing all domains of this filter
     *
     * @var array
     */
    private $domains = array();

    /**
     * Create a new domain and return it
     *
     * @param  String $name     The field to be handled by this domain
     *
     * @return Domain           The created domain object
     */
    public function createFilterDomain($name)
    {
        $domain = new Domain(trim($name));

        $this->domains[] = $domain;
        return $domain;
    }

    /**
     * Set the default domain (used if no domain identifier is given to the query) to the given one
     *
     * @param Domain $domain    The domain to use as the default. Will be added to the domain list if not present yet
     */
    public function setDefaultDomain(Domain $domain)
    {
        if (!in_array($domain, $this->domains)) {
            $this->domains[] = $domain;
        }
        $this->defaultDomain = $domain;
    }

    /**
     * Return the default domaon
     *
     * @return Domain       Return either the domain that has been explicitly set as the default domain or the first
     *                      added. If no domain has been added yet null is returned
     */
    public function getDefaultDomain()
    {
        if ($this->defaultDomain !== null) {
            return $this->defaultDomain;
        } else if (count($this->domains) > 0) {
            return $this->domains[0];
        }
        return null;
    }

    /**
     * Add a domain to this filter
     *
     * @param Domain $domain    The domain to add
     * @return self             Fluent interface
     */
    public function addDomain(Domain $domain)
    {
        $this->domains[] = $domain;
        return $this;
    }

    /**
     * Return all domains that could match the given query
     *
     * @param String $query     The query to search matching domains for
     *
     * @return array            An array containing 0..* domains that could handle the query
     */
    public function getDomainsForQuery($query)
    {
        $domains = array();
        foreach ($this->domains as $domain) {
            if ($domain->handlesQuery($query)) {
                $domains[] = $domain;
            }
        }
        return $domains;
    }

    /**
     * Return the first domain matching for this query (or the default domain)
     *
     * @param  String $query        The query to search for a domain
     * @return Domain               A matching domain or the default domain if no domain is matching
     */
    public function getFirstDomainForQuery($query)
    {
        $domains = $this->getDomainsForQuery($query);
        if (empty($domains)) {
            $domain = $this->getDefaultDomain();
        } else {
            $domain = $domains[0];
        }
        return $domain;
    }

    /**
     * Return proposals for the given query part
     *
     * @param String $query    The part of the query that this specifier should parse
     *
     * @return array            An array containing 0..* proposal text tokens
     */
    public function getProposalsForQuery($query)
    {
        $query = $this->getLastQueryPart($query);
        $proposals = array();
        $domains = $this->getDomainsForQuery($query);
        foreach ($domains as $domain) {
            $proposals = array_merge($proposals, $domain->getProposalsForQuery($query));
        }
        if (empty($proposals) && $this->getDefaultDomain()) {
            foreach ($this->domains as $domain) {
                if (stripos($domain->getLabel(), $query) === 0 || $query == '') {
                    $proposals[] = self::markDifference($domain->getLabel(), $query);
                }
            }
            $proposals = array_merge($proposals, $this->getDefaultDomain()->getProposalsForQuery($query));
        }
        return $proposals;
    }

    /**
     * Split the query at the next conjunction and return a 3 element array containing (left, conjunction, right)
     *
     * @param $query        The query to split
     * @return array        An three element tupel in the form array($left, $conjunction, $right)
     */
    private function splitQueryAtNextConjunction($query)
    {
        $delimiter = array('AND', 'OR');
        $inStr = false;
        for ($i = 0; $i < strlen($query); $i++) {
            // Skip strings
            $char = $query[$i];
            if ($inStr) {
                if ($char == $inStr) {
                    $inStr = false;
                }
                continue;
            }
            if ($char === '\'' || $char === '"') {
                $inStr = $char;
                continue;
            }
            foreach ($delimiter as $delimiterString) {
                $delimiterLength = strlen($delimiterString);
                if (strtoupper(substr($query, $i, $delimiterLength)) === $delimiterString) {
                    // Delimiter, split into left, middle, right part
                    $nextPartOffset = $i + $delimiterLength;
                    $left = substr($query, 0, $i);
                    $conjunction = $delimiterString;
                    $right = substr($query, $nextPartOffset);
                    return array(trim($left), $conjunction, trim($right));
                }
            }
        }
        return array($query, null, null);
    }

    /**
     * Return the last part of the query
     *
     * Mostly required for generating query proposals
     *
     * @param $query    The query to scan for the last part
     * @return mixed    An string containing the rightmost query
     */
    private function getLastQueryPart($query)
    {
        $right = $query;
        do {
            list($left, $conjuction, $right)  = $this->splitQueryAtNextConjunction($right);
        } while($conjuction !== null);
        return $left;
    }

    /**
     * Create a query tree containing this filter
     *
     * Query parts that couldn't be parsed can be retrieved with Filter::getIgnoredQueryParts
     *
     * @param  String $query    The query string to parse into a query tree
     * @return Tree             The resulting query tree (empty for invalid queries)
     */
    public function createQueryTreeForFilter($query)
    {
        $this->ignoredQueryParts = array();
        $right = $query;
        $domain = null;
        $tree = new Tree();
        do {
            list($left, $conjunction, $right) = $this->splitQueryAtNextConjunction($right);
            $domain = $this->getFirstDomainForQuery($left);
            if ($domain === null) {
                $this->ignoredQueryParts[] = $left;
                continue;
            }

            $node = $domain->convertToTreeNode($left);
            if (!$node) {
                $this->ignoredQueryParts[] = $left;
                continue;
            }
            $tree->insert($node);

            if ($conjunction === 'AND') {
                $tree->insert(Node::createAndNode());
            } elseif($conjunction === 'OR') {
                $tree->insert(Node::createOrNode());
            }

        } while ($right !== null);
        return $tree;
    }

    /**
     * Return all parts that couldn't be parsed in the last createQueryTreeForFilter run
     *
     * @return array    An array containing invalid/non-parseable query strings
     */
    public function getIgnoredQueryParts()
    {
        return $this->ignoredQueryParts;
    }
}