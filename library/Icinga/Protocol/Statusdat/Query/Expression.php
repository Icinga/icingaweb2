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

use Icinga\Protocol\Ldap\Exception;

class Expression implements IQueryPart
{
    /**
     *
     */
    const ENC_NUMERIC = 0;

    /**
     *
     */
    const ENC_SET = 0;

    /**
     *
     */
    const ENC_STRING = 0;

    /**
     * @var string
     */
    private $expression;

    /**
     * @var null
     */
    private $field = null;

    /**
     * @var array
     */
    private $basedata = array();

    /**
     * @var null
     */
    private $function = null;

    /**
     * @var string
     */
    private $value = "";

    /**
     * @var null
     */
    private $operator = null;

    /**
     * Optional query information
     *
     * @var null
     */
    private $query = null;

    /**
     * @var null
     */
    private $name = null;

    /**
     * @var null
     */
    public $CB = null;

    /**
     * @param $token
     * @throws \Exception
     */
    private function getOperatorType($token)
    {
        switch (strtoupper($token)) {
            case ">":
                $this->CB = "isGreater";
                break;
            case "<":
                $this->CB = "isLess";
                break;
            case ">=":
                $this->CB = "isGreaterEq";
                break;
            case "<=":
                $this->CB = "isLessEq";
                break;
            case "=":
                $this->CB = "isEqual";
                break;
            case "LIKE":
                $this->CB = "isLike";
                break;
            case "NOT_LIKE":
                $this->CB = "isNotLike";
                break;
            case "!=":
                $this->CB = "isNotEqual";
                break;
            case "IN":
                $this->CB = "isIn";
                break;
            case "NOT_IN":
                $this->CB = "isNotIn";
                break;
            default:
                throw new \Exception("Unknown operator $token in expression $this->expression !");
        }
    }

    /**
     * @param $tokens
     * @return mixed
     */
    private function extractAggregationFunction(&$tokens)
    {
        $token = $tokens[0];
        $value = array();
        if (preg_match("/COUNT\{(.*)\}/", $token, $value) == false) {
            return $token;
        }
        $this->function = "count";
        $tokens[0] = $value[1];

        return null;
    }

    /**
     * @param $values
     */
    private function parseExpression(&$values)
    {
        $tokenized = preg_split("/ +/", trim($this->expression), 3);
        $this->extractAggregationFunction($tokenized);
        if (count($tokenized) != 3) {
            echo(
                "Currently statusdat query expressions must be in "
                . "the format FIELD OPERATOR ? or FIELD OPERATOR :value_name"
            );
        }

        $this->fields = explode(".", trim($tokenized[0]));
        $this->field = $this->fields[count($this->fields) - 1];

        $this->getOperatorType(trim($tokenized[1]));
        $tokenized[2] = trim($tokenized[2]);

        if ($tokenized[2][0] === ":") {
            $this->name = substr($tokenized, 1);
            $this->value = $values[$this->name];
        } else {
            if ($tokenized[2] === "?") {
                $this->value = array_shift($values);
            } else {
                $this->value = trim($tokenized[2]);
            }
        }

    }

    /**
     * @param $expression
     * @param $values
     * @return $this
     */
    public function fromString($expression, &$values)
    {
        $this->expression = $expression;
        $this->parseExpression($values);
        return $this;
    }

    /**
     * @param null $expression
     * @param array $values
     */
    public function __construct($expression = null, &$values = array())
    {
        if ($expression) {
            if (!is_array($values)) {
                $values = array($values);
            }
            $this->fromString($expression, $values);
        }

    }

    /**
     * @param array $base
     * @param array $idx
     * @return array|mixed
     */
    public function filter(array &$base, &$idx = array())
    {
        if (!$idx) {
            $idx = array_keys($base);
        }
        $this->basedata = $base;
        return array_filter($idx, array($this, "filterFn"));
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return null
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param $idx
     * @return bool
     */
    protected function filterFn($idx)
    {
        $values = $this->getFieldValues($idx);

        if ($values === false) {
            return false;
        }

        if ($this->CB == "isIn" || $this->CB == "isNotIn") {
            $cmpValues = is_array($this->value) ? $this->value : array($this->value);
            foreach ($cmpValues as $cmpValue) {
                $this->value = $cmpValue;
                foreach ($values as $value) {
                    if ($this->CB == "isIn" && $this->isLike($value)) {
                        $this->value = $cmpValues;
                        return true;
                    } elseif ($this->CB == "isNotIn" && $this->isNotLike($value)) {
                        $this->value = $cmpValues;
                        return true;
                    }
                }
            }
            $this->value = $cmpValues;
            return false;
        }

        if ($this->function) {
            $values = call_user_func($this->function, $values);
            if (!is_array($values)) {
                $values = array($values);
            }
        }
        foreach ($values as $val) {

            if (!is_string($val) && !is_numeric($val) && is_object($val)) {
                if (isset($val->service_description)) {
                    $val = $val->service_description;
                } elseif(isset($val->host_name)) {
                    $val = $val->host_name;
                } else {
                    return false;
                }
            }
            if ($this->{$this->CB}($val)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $idx
     * @return array
     */
    private function getFieldValues($idx)
    {
        $res = $this->basedata[$idx];

        foreach ($this->fields as $field) {
            if (!is_array($res)) {
                if ($this->query) {
                    $res = $this->query->get($res, $field);
                    continue;
                }

                if (!isset($res->$field)) {
                    $res = array();
                    break;
                }
                $res = $res->$field;
                continue;
            }

            // it can be that an element contains more than one value, like it
            // happens when using comments, in this case we have to create a new
            // array that contains the values/objects we're searching
            $swap = array();
            foreach ($res as $sub) {
                if ($this->query) {
                    $swap[] = $this->query->get($sub, $field);
                    continue;
                }
                if (!isset($sub->$field)) {
                    continue;
                }
                if (!is_array($sub->$field)) {
                    $swap[] = $sub->$field;
                } else {
                    $swap = array_merge($swap, $sub->$field);
                }
            }
            $res = $swap;
        }
        if (!is_array($res)) {
            return array($res);
        }

        return $res;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isGreater($value)
    {
        return $value > $this->value;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isLess($value)
    {
        return $value < $this->value;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isLike($value)
    {
        return preg_match("/^" . str_replace("%", ".*", $this->value) . "$/", $value) ? true : false;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isNotLike($value)
    {
        return !preg_match("/^" . str_replace("%", ".*", $this->value) . "$/", $value) ? true : false;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isEqual($value)
    {
        if (!is_numeric($value)) {
            return strtolower($value) == strtolower($this->value);
        }
        return $value == $this->value;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isNotEqual($value)
    {
        return $value != $this->value;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isGreaterEq($value)
    {
        return $value >= $this->value;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isLessEq($value)
    {
        return $value <= $this->value;
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
