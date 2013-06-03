<?php

namespace Icinga\Protocol\Statusdat\Query;

class Group implements IQueryPart
{
    const GROUP_BEGIN = "(";
    const GROUP_END = ")";
    const CONJUNCTION_AND = "AND ";
    const CONJUNCTION_OR = "OR ";

    const EXPRESSION = 0;
    const EOF = -1;

    const TYPE_AND = "AND";
    const TYPE_OR = "OR";

    private $items = array();
    private $parsePos = 0;
    private $expression = "";
    private $expressionClass = null;
    private $type = "";
    private $subExpressionStart = 0;
    private $subExpressionLength = 0;
    private $value;

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getType()
    {
        return $this->type ? $this->type : self::TYPE_AND;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    private function tokenize()
    {
        $token = 0;
        $subgroupCount = 0;
        while ($token != self::EOF) {

            $token = $this->getNextToken();

            if ($token === self::GROUP_BEGIN) {

                if ($subgroupCount == 0) // check if this is a nested group, if so then it's considered part of the subexpression
                    $this->startNewSubExpression();
                $subgroupCount++;
                continue;
            }
            if ($token === self::GROUP_END) {
                if ($subgroupCount < 1)
                    throw new \Exception("Invalid Query: unexpected ')' at pos " . $this->parsePos);
                $subgroupCount--;
                if ($subgroupCount == 0) // check if this is a nested group, if so then it's considered part of the subexpression
                    $this->addSubgroupFromExpression();
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
        if ($subgroupCount > 0)
            throw new \Exception("Unexpected end of query, are you missing a parenthesis?");

        $this->startNewSubExpression();
    }

    private function createImplicitGroup($type)
    {
        $group = new Group();
        $group->setType($type);
        $group->addItem(array_pop($this->items));

        $group->fromString(substr($this->expression, $this->parsePos), $this->value, $this->expressionClass);
        $this->items[] = $group;
        $this->parsePos = strlen($this->expression);

    }

    private function startNewSubExpression()
    {
        if ($this->getCurrentSubExpression() != "") {
            if (!$this->expressionClass)
                $this->items[] = new Expression($this->getCurrentSubExpression(), $this->value);
            else
                $this->items[] = new $this->expressionClass($this->getCurrentSubExpression(), $this->value);
        }

        $this->subExpressionStart = $this->parsePos;
        $this->subExpressionLength = 0;
    }

    private function getCurrentSubExpression()
    {

        return substr($this->expression, $this->subExpressionStart, $this->subExpressionLength);
    }

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

    private function isEOF()
    {
        if ($this->parsePos >= strlen($this->expression))
            return true;
        return false;
    }

    private function getNextToken()
    {
        if ($this->isEOF())
            return self::EOF;

        // skip whitespaces
        while ($this->expression[$this->parsePos] == " ") {
            $this->parsePos++;
            if ($this->isEOF())
                return self::EOF;
        }
        if ($this->expression[$this->parsePos] == self::GROUP_BEGIN) {
            $this->parsePos++;
            return self::GROUP_BEGIN;
        }
        if ($this->expression[$this->parsePos] == self::GROUP_END) {
            $this->parsePos++;
            return self::GROUP_END;
        }
        if (substr_compare($this->expression, self::CONJUNCTION_AND, $this->parsePos, strlen(self::CONJUNCTION_AND), true) === 0) {
            $this->parsePos += strlen(self::CONJUNCTION_AND);
            return self::CONJUNCTION_AND;
        }
        if (substr_compare($this->expression, self::CONJUNCTION_OR, $this->parsePos, strlen(self::CONJUNCTION_OR), true) === 0) {
            $this->parsePos += strlen(self::CONJUNCTION_OR);
            return self::CONJUNCTION_OR;
        }
        $this->parsePos++;
        return self::EXPRESSION;
    }

    public function addItem($ex)
    {
        $this->items[] = $ex;
        return $this;
    }

    public function fromString($expression, &$value = array(), $expressionClass = null)
    {
        $this->expression = $expression;
        $this->value = & $value;
        $this->expressionClass = $expressionClass;

        $this->tokenize();
        return $this;
    }


    public function __construct($expression = null, &$value = array())
    {
        if ($expression)
            $this->fromString($expression, $value);
    }

    public function filter(array &$base, &$idx = null)
    {

        if ($this->type == self::TYPE_OR) {
            $idx = array();
            foreach ($this->items as &$subFilter) {
                $idx += $subFilter->filter($base, array_keys($base));
            }
        } else {
            if (!$idx)
                $idx = array_keys($base);
            foreach ($this->items as $subFilter) {
                $idx = array_intersect($idx, $subFilter->filter($base, $idx));
            }
        }

        return $idx;
    }
}

