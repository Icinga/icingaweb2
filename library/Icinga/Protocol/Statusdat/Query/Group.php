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

namespace Icinga\Protocol\Statusdat\Query;

/**
 * Class Group
 * @package Icinga\Protocol\Statusdat\Query
 */
class Group implements IQueryPart
{
    /**
     *
     */
    const GROUP_BEGIN = "(";

    /**
     *
     */
    const GROUP_END = ")";

    /**
     *
     */
    const CONJUNCTION_AND = "AND ";

    /**
     *
     */
    const CONJUNCTION_OR = "OR ";

    /**
     *
     */
    const EXPRESSION = 0;

    /**
     *
     */
    const EOF = -1;

    /**
     *
     */
    const TYPE_AND = "AND";

    /**
     *
     */
    const TYPE_OR = "OR";

    /**
     * @var array
     */
    private $items = array();

    /**
     * @var int
     */
    private $parsePos = 0;

    /**
     * @var string
     */
    private $expression = "";

    /**
     * @var null
     */
    private $expressionClass = null;

    /**
     * @var string
     */
    private $type = "";

    /**
     * @var int
     */
    private $subExpressionStart = 0;

    /**
     * @var int
     */
    private $subExpressionLength = 0;

    /**
     * Optional query to use
     *
     * @var Query
     */
    private $query = null;

    /**
     * @var
     */
    private $value;

    /**
     * @param $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type ? $this->type : self::TYPE_AND;
    }

    /**
     * @param $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @throws \Exception
     */
    private function tokenize()
    {
        $token = 0;
        $subgroupCount = 0;
        while ($token != self::EOF) {

            $token = $this->getNextToken();

            if ($token === self::GROUP_BEGIN) {

                /**
                 * check if this is a nested group, if so then it's
                 * considered part of the subexpression
                 */
                if ($subgroupCount == 0) {
                    $this->startNewSubExpression();
                }
                $subgroupCount++;
                continue;
            }
            if ($token === self::GROUP_END) {
                if ($subgroupCount < 1) {
                    throw new \Exception("Invalid Query: unexpected ')' at pos " . $this->parsePos);
                }
                $subgroupCount--;
                /*
                 * check if this is a nested group, if so then it's
                 * considered part of the subexpression
                 */
                if ($subgroupCount == 0) {
                    $this->addSubgroupFromExpression();
                }
                continue;
            }

            if ($token === self::CONJUNCTION_AND && $subgroupCount == 0) {
                $this->startNewSubExpression();
                if ($this->type != self::TYPE_AND && $this->type != "") {
                    $this->createImplicitGroup(self::TYPE_AND);
                    break;
                } else {
                    $this->type = self::TYPE_AND;
                }
                continue;
            }
            if ($token === self::CONJUNCTION_OR && $subgroupCount == 0) {
                $this->startNewSubExpression();
                if ($this->type != self::TYPE_OR && $this->type != "") {
                    $this->createImplicitGroup(self::TYPE_OR);
                    break;
                } else {

                    $this->type = self::TYPE_OR;
                }
                continue;
            }

            $this->subExpressionLength = $this->parsePos - $this->subExpressionStart;
        }
        if ($subgroupCount > 0) {
            throw new \Exception("Unexpected end of query, are you missing a parenthesis?");
        }

        $this->startNewSubExpression();
    }

    /**
     * @param $type
     */
    private function createImplicitGroup($type)
    {
        $group = new Group();
        $group->setType($type);
        $group->addItem(array_pop($this->items));

        $group->fromString(substr($this->expression, $this->parsePos), $this->value, $this->expressionClass);
        $this->items[] = $group;
        $this->parsePos = strlen($this->expression);

    }

    /**
     *
     */
    private function startNewSubExpression()
    {
        if ($this->getCurrentSubExpression() != "") {
            if (!$this->expressionClass) {
                $this->items[] = new Expression($this->getCurrentSubExpression(), $this->value);
            } else {
                $this->items[] = new $this->expressionClass($this->getCurrentSubExpression(), $this->value);
            }
        }

        $this->subExpressionStart = $this->parsePos;
        $this->subExpressionLength = 0;
    }

    /**
     * @return string
     */
    private function getCurrentSubExpression()
    {

        return substr($this->expression, $this->subExpressionStart, $this->subExpressionLength);
    }

    /**
     *
     */
    private function addSubgroupFromExpression()
    {

        if (!$this->expressionClass) {
            $this->items[] = new Group($this->getCurrentSubExpression(), $this->value);
        } else {
            $group = new Group();
            $group->fromString($this->getCurrentSubExpression(), $this->value, $this->expressionClass);
            $this->items[] = $group;
        }
        $this->subExpressionStart = $this->parsePos;
        $this->subExpressionLength = 0;
    }

    /**
     * @return bool
     */
    private function isEOF()
    {
        if ($this->parsePos >= strlen($this->expression)) {
            return true;
        }
        return false;
    }

    /**
     * @return int|string
     */
    private function getNextToken()
    {
        if ($this->isEOF()) {
            return self::EOF;
        }

        // skip whitespaces
        while ($this->expression[$this->parsePos] == " ") {
            $this->parsePos++;
            if ($this->isEOF()) {
                return self::EOF;
            }
        }
        if ($this->expression[$this->parsePos] == self::GROUP_BEGIN) {
            $this->parsePos++;
            return self::GROUP_BEGIN;
        }
        if ($this->expression[$this->parsePos] == self::GROUP_END) {
            $this->parsePos++;
            return self::GROUP_END;
        }
        if (substr_compare(
            $this->expression,
            self::CONJUNCTION_AND,
            $this->parsePos,
            strlen(self::CONJUNCTION_AND),
            true
        ) === 0) {
            $this->parsePos += strlen(self::CONJUNCTION_AND);
            return self::CONJUNCTION_AND;
        }
        if (substr_compare(
            $this->expression,
            self::CONJUNCTION_OR,
            $this->parsePos,
            strlen(self::CONJUNCTION_OR),
            true
        ) === 0) {
            $this->parsePos += strlen(self::CONJUNCTION_OR);
            return self::CONJUNCTION_OR;
        }
        $this->parsePos++;
        return self::EXPRESSION;
    }

    /**
     * @param $ex
     * @return $this
     */
    public function addItem($ex)
    {
        $this->items[] = $ex;
        return $this;
    }

    /**
     * @param $expression
     * @param array $value
     * @param null $expressionClass
     * @return $this
     */
    public function fromString($expression, &$value = array(), $expressionClass = null)
    {
        $this->expression = $expression;
        $this->value = & $value;
        $this->expressionClass = $expressionClass;

        $this->tokenize();
        return $this;
    }

    /**
     * @param null $expression
     * @param array $value
     */
    public function __construct($expression = null, &$value = array())
    {
        if ($expression) {
            $this->fromString($expression, $value);
        }
    }

    /**
     * @param array $base
     * @param null $idx
     * @return array|null
     */
    public function filter(array &$base, &$idx = null)
    {
        if ($this->type == self::TYPE_OR) {
            $idx = array();
            foreach ($this->items as &$subFilter) {
                $baseKeys = array_keys($base);
                $subFilter->setQuery($this->query);
                $idx += $subFilter->filter($base, $baseKeys);
            }
        } else {
            if (!$idx) {
                $idx = array_keys($base);
            }
            foreach ($this->items as $subFilter) {
                $subFilter->setQuery($this->query);
                $idx = array_intersect($idx, $subFilter->filter($base, $idx));
            }
        }

        return $idx;
    }

    /**
     * Add additional information about the query this filter belongs to
     *
     * @param $query
     * @return mixed
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }


}
