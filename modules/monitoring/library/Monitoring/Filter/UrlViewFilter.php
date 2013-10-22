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

namespace Icinga\Module\Monitoring\Filter;

use Icinga\Filter\Filterable;
use Icinga\Filter\Query\Tree;
use Icinga\Filter\Query\Node;
use Icinga\Web\Request;
use Icinga\Web\Url;
use Icinga\Application\Logger;

/**
 * Converter class that allows to create Query Trees from an request query and vice versa
 */
class UrlViewFilter
{
    const FILTER_TARGET     = 'target';
    const FILTER_OPERATOR   = 'operator';
    const FILTER_VALUE      = 'value';
    const FILTER_ERROR      = 'error';

    /**
     * An optional target filterable to use for validation and normalization
     *
     * @var Filterable
     */
    private $target;

    private $supportedConjunctions = array('&'/*, '|'*/);


    /**
     * Create a new ViewFilter
     *
     * @param Filterable $target        An optional Filterable to use for validation and normalization
     */
    public function __construct(Filterable $target = null)
    {
        $this->target = $target;
    }


    /**
     * Return an URL filter string for the given query tree
     *
     * @param Tree $filter      The query tree to parse
     * @return null|string      The string representation of the query
     */
    public function fromTree(Tree $filter)
    {
        if ($filter->root === null) {
            return '';
        }
        if ($this->target) {
            $filter = $filter->getCopyForFilterable($this->target);
        }
        $filter = $this->normalizeTreeNode($filter->root);
        $filter->root = $filter->normalizeTree($filter->root);
        return $this->convertNodeToUrlString($filter->root);
    }

    private function insertNormalizedOperatorNode($node, Tree $subTree = null)
    {

        $searchNode = $subTree->findNode(Node::createOperatorNode($node->operator, $node->left, null));
        if ( $searchNode !== null) {
            $result = array();
            foreach ($node->right as $item) {
                if (stripos($item, '*')) {
                    $subTree->insert(Node::createOperatorNode($node->operator, $node->left, $item));
                } else {
                    $result = $result + $node->right;
                }
            }
            $searchNode->right = array_merge($searchNode->right, $result);
        } else {
            $subTree->insert($node);
        }
    }

    public function normalizeTreeNode($node, Tree $subTree = null)
    {
        $subTree = $subTree ? $subTree : new Tree();
        if (!$node) {
            return $subTree;
        }
        if ($node->type === Node::TYPE_OPERATOR) {
            $this->insertNormalizedOperatorNode($node, $subTree);
        } else {
            $subTree->insert($node->type === Node::TYPE_AND ? Node::createAndNode() : Node::createOrNode());
            $subTree = $this->normalizeTreeNode($node->left, $subTree);
            $subTree = $this->normalizeTreeNode($node->right, $subTree);
        }
        return $subTree;
    }

    /**
     * Parse the given given url and return a query tree
     *
     * @param string $query     The query to parse, if not given $_SERVER['QUERY_STRING'] is used
     * @return Tree             A tree representing the valid parts of the filter
     */
    public function parseUrl($query = '')
    {
        if (!isset($_SERVER['QUERY_STRING'])) {
            $_SERVER['QUERY_STRING'] = $query;
        }
        $query = $query ? $query : $_SERVER['QUERY_STRING'];

        $tokens = $this->tokenizeQuery($query);
        $tree = new Tree();
        foreach ($tokens as $token) {
            if ($token === '&') {
                $tree->insert(Node::createAndNode());
            } elseif ($token === '|') {
                $tree->insert(Node::createOrNode());
            } elseif (is_array($token)) {
                $tree->insert(
                    Node::createOperatorNode(
                        trim($token[self::FILTER_OPERATOR]),
                        trim($token[self::FILTER_TARGET]),
                        $token[self::FILTER_VALUE]
                    )
                );
            }
        }
        return $tree->getCopyForFilterable($this->target);
    }

    public function fromRequest($request)
    {
        if ($request->getParam('query')) {
            return $this->parseUrl(urldecode($request->getParam('query')));
        } else {
            return $this->parseUrl(parse_url($request->getBaseUrl(), PHP_URL_QUERY));
        }
    }

    /**
     * Convert a tree node and it's subnodes to a request string
     *
     * @param Node $node        The node to convert
     * @return null|string      A string representing the node in the url form or null if it's invalid
     *                          ( or if the Filterable doesn't support the attribute)
     */
    private function convertNodeToUrlString(Node $node)
    {
        $left = null;
        $right = null;
        if ($node->type === Node::TYPE_OPERATOR) {
            if ($this->target && !$this->target->isValidFilterTarget($node->left)) {
                return null;
            }
            $values = array();
            foreach ($node->right as $item) {
                $values[] = urlencode($item);

            }
            return urlencode($node->left) . $node->operator . join(',', $values);
        }
        if ($node->left) {
            $left = $this->convertNodeToUrlString($node->left);
        }
        if ($node->right) {
            $right = $this->convertNodeToUrlString($node->right);
        }

        if ($left && !$right) {
            return null;
        } elseif ($right && !$left) {
            return $this->convertNodeToUrlString($node->right);
        } elseif (!$left && !$right) {
            return null;
        }

        $operator = ($node->type === Node::TYPE_AND) ? '&' : '|';
        return $left . $operator . $right;
    }

    /**
     * Split the query into seperate tokens that can be parsed seperately
     *
     * Tokens are associative arrays in the following form
     *
     * array(
     *      self::FILTER_TARGET   => 'Attribute',
     *      self::FILTER_OPERATOR => '!=',
     *      self::FILTER_VALUE    => array('Value')
     * )
     *
     * @param  String $query        The query to tokenize
     * @return array                An array of tokens
     *
     * @see self::parseTarget()     The tokenize function for target=value expressions
     * @see self::parseValue()      The tokenize function that only retrieves a value (e.g. target=value|value2)
     */
    private function tokenizeQuery($query)
    {
        $tokens = array();
        $state = self::FILTER_TARGET;
        $query = urldecode($query);

        for ($i = 0; $i <= strlen($query); $i++) {
            switch ($state) {
                case self::FILTER_TARGET:
                    list($i, $state) = $this->parseTarget($query, $i, $tokens);
                    break;
                case self::FILTER_VALUE:
                    list($i, $state) = $this->parseValue($query, $i, $tokens);
                    break;
                case self::FILTER_ERROR:
                    list($i, $state) = $this->skip($query, $i);
                    break;
            }
        }

        return $tokens;
    }

    /**
     * Return the operator matching the given query, or an empty string if none matches
     *
     * @param String  $query        The query to extract the operator from
     * @param integer $i            The offset to use in the query string
     *
     * @return string               The operator string that matches best
     */
    private function getMatchingOperator($query, $i)
    {
        $operatorToUse = '';
        foreach (Node::$operatorList as $operator) {
            if (substr($query, $i, strlen($operator)) === $operator) {
                if (strlen($operatorToUse) < strlen($operator)) {
                    $operatorToUse = $operator;
                }
            }
        }

        return $operatorToUse;
    }

    /**
     * Parse a new expression until the next conjunction or end and return the matching token for it
     *
     * @param String    $query              The query string to create a token from
     * @param Integer   $currentPos         The offset to use in the query string
     * @param array     $tokenList          The existing token list to add the token to
     *
     * @return array                        A two element array with the new offset in the beginning and the new
     *                                      parse state as the second parameter
     */
    private function parseTarget($query, $currentPos, array &$tokenList)
    {
        $i = $currentPos;

        for ($i; $i < strlen($query); $i++) {
            $currentChar = $query[$i];
            // test if operator matches
            $operator = $this->getMatchingOperator($query, $i);

            // Test if we're at an operator field right now, then add the current token
            // without value to the tokenlist
            if ($operator !== '') {
                $tokenList[] = array(
                    self::FILTER_TARGET     => substr($query, $currentPos, $i - $currentPos),
                    self::FILTER_OPERATOR   => $operator
                );
                // -1 because we're currently pointing at the first character of the operator
                $newOffset = $i + strlen($operator) - 1;
                return array($newOffset, self::FILTER_VALUE);
            }

            // Implicit value token (test=1|2)
            if (in_array($currentChar, $this->supportedConjunctions) || $i + 1 == strlen($query)) {
                $nrOfSymbols = count($tokenList);
                if ($nrOfSymbols <= 2) {
                    return array($i, self::FILTER_TARGET);
                }

                $lastState = &$tokenList[$nrOfSymbols-2];

                if (is_array($lastState)) {
                    $tokenList[] = array(
                        self::FILTER_TARGET     => $lastState[self::FILTER_TARGET],
                        self::FILTER_OPERATOR   => $lastState[self::FILTER_OPERATOR],
                    );
                    return $this->parseValue($query, $currentPos, $tokenList);
                }
                return array($i, self::FILTER_TARGET);
            }
        }

        return array($i, self::FILTER_TARGET);
    }

    /**
     * Parse the value part of a query string, starting at current pos
     *
     * This expects an token without value to be placed in the tokenList stack
     *
     * @param String    $query              The query string to create a token from
     * @param Integer   $currentPos         The offset to use in the query string
     * @param array     $tokenList          The existing token list to add the token to
     *
     * @return array                        A two element array with the new offset in the beginning and the new
     *                                      parse state as the second parameter
     */
    private function parseValue($query, $currentPos, array &$tokenList)
    {

        $i = $currentPos;
        $nrOfSymbols = count($tokenList);

        if ($nrOfSymbols == 0) {
            return array($i, self::FILTER_TARGET);
        }
        $lastState = &$tokenList[$nrOfSymbols-1];
        for ($i; $i < strlen($query); $i++) {
            $currentChar = $query[$i];
            if (in_array($currentChar,  $this->supportedConjunctions)) {
                break;
            }
        }
        $length = $i - $currentPos;
        // No value given
        if ($length === 0) {
            array_pop($tokenList);
            array_pop($tokenList);
            return array($currentPos, self::FILTER_TARGET);
        }
        $lastState[self::FILTER_VALUE] = explode(',', substr($query, $currentPos, $length));

        if (in_array($currentChar,  $this->supportedConjunctions)) {
            $tokenList[] = $currentChar;
        }
        return array($i, self::FILTER_TARGET);
    }

    /**
     * Skip a query substring until the next conjunction appears
     *
     * @param String    $query              The query string to skip the next token
     * @param Integer   $currentPos         The offset to use in the query string
     *
     * @return array                        A two element array with the new offset in the beginning and the new
     *                                      parse state as the second parameter
     */
    private function skip($query, $currentPos)
    {
        for ($i = $currentPos; strlen($query); $i++) {
            $currentChar = $query[$i];
            if (in_array($currentChar, $this->supportedConjunctions)) {
                return array($i, self::FILTER_TARGET);
            }
        }
    }
}
