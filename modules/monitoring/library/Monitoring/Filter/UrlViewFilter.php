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


use Icinga\Filter\Query\Tree;
use Icinga\Filter\Query\Node;
use Icinga\Web\Url;
use Icinga\Application\Logger;

class UrlViewFilter
{
    const FILTER_TARGET     = 'target';
    const FILTER_OPERATOR   = 'operator';
    const FILTER_VALUE      = 'value';
    const FILTER_ERROR      = 'error';

    private function evaluateNode(Node $node)
    {
        switch($node->type) {

            case Node::TYPE_OPERATOR:
                return urlencode($node->left) . $node->operator . urlencode($node->right);
            case Node::TYPE_AND:
                return $this->evaluateNode($node->left) . '&' . $this->evaluateNode($node->right);
            case Node::TYPE_OR:
                return $this->evaluateNode($node->left) . '|' . $this->evaluateNode($node->right);
        }
    }

    public function fromTree(Tree $filter)
    {
        return $this->evaluateNode($filter->root);
    }

    public function parseUrl($query = "")
    {
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
                        $token[self::FILTER_OPERATOR],
                        $token[self::FILTER_TARGET],
                        $token[self::FILTER_VALUE]
                    )
                );
            }
        }
        return $tree;

    }


    private function tokenizeQuery($query)
    {
        $tokens = array();
        $state = self::FILTER_TARGET;

        for ($i = 0;$i <= strlen($query); $i++) {

            switch ($state) {
                case self::FILTER_TARGET:
                    list($i, $state) = $this->parseTarget($query, $i, $tokens);
                    break;
                case self::FILTER_VALUE:
                    list($i, $state) = $this->parseValue($query, $i, $tokens);
                    break;
                case self::FILTER_ERROR:
                    list($i, $state) = $this->skip($query, $i, $tokens);
                    break;
            }
        }

        return $tokens;
    }

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

    private function parseTarget($query, $currentPos, array &$tokenList)
    {
        $conjunctions = array('&', '|');
        $i = $currentPos;
        for ($i; $i < strlen($query); $i++) {
            $currentChar = $query[$i];
            // test if operator matches
            $operator = $this->getMatchingOperator($query, $i);

            // Test if we're at an operator field right now, then add the current token
            // without value to the tokenlist
            if($operator !== '') {
                $tokenList[] = array(
                    self::FILTER_TARGET     => urldecode(substr($query, $currentPos, $i - $currentPos)),
                    self::FILTER_OPERATOR   => $operator
                );
                // -1 because we're currently pointing at the first character of the operator
                $newOffset = $i + strlen($operator) - 1;
                return array($newOffset, self::FILTER_VALUE);
            }

            // Implicit value token (test=1|2)
            if (in_array($currentChar, $conjunctions) || $i + 1 == strlen($query)) {
                $nrOfSymbols = count($tokenList);
                if ($nrOfSymbols <= 2) {
                    return array($i, self::FILTER_TARGET);
                }

                $lastState = &$tokenList[$nrOfSymbols-2];

                if (is_array($lastState)) {
                    $tokenList[] = array(
                        self::FILTER_TARGET     => urldecode($lastState[self::FILTER_TARGET]),
                        self::FILTER_OPERATOR   => $lastState[self::FILTER_OPERATOR],
                    );
                    return $this->parseValue($query, $currentPos, $tokenList);
                }
                return array($i, self::FILTER_TARGET);
            }
        }

        return array($i, self::FILTER_TARGET);
    }


    private function parseValue($query, $currentPos, array &$tokenList)
    {

        $i = $currentPos;
        $conjunctions = array('&', '|');
        $nrOfSymbols = count($tokenList);

        if ($nrOfSymbols == 0) {
            return array($i, self::FILTER_TARGET);
        }
        $lastState = &$tokenList[$nrOfSymbols-1];
        for ($i; $i < strlen($query); $i++) {
            $currentChar = $query[$i];
            if (in_array($currentChar, $conjunctions)) {
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
        $lastState[self::FILTER_VALUE] = urldecode(substr($query, $currentPos, $length));

        if (in_array($currentChar, $conjunctions)) {
            $tokenList[] = $currentChar;
        }
        return array($i, self::FILTER_TARGET);
    }

    private function skip($query, $currentPos, array &$tokenList)
    {
        $conjunctions = array('&', '|');
        for ($i = $currentPos; strlen($query); $i++) {
            $currentChar = $query[$i];
            if (in_array($currentChar, $conjunctions)) {
                return array($i, self::FILTER_TARGET);
            }
        }
    }


}