<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Filter;

class FilterQueryString
{
    protected $string;

    protected $pos;

    protected $debug = array();

    protected $reportDebug = false;

    protected $length;

    protected function __construct()
    {
    }

    protected function debug($msg, $level = 0, $op = null)
    {
        if ($op === null) {
            $op = 'NULL';
        }
        $this->debug[] = sprintf(
            '%s[%d=%s] (%s): %s',
            str_repeat('* ', $level),
            $this->pos,
            $this->string[$this->pos - 1],
            $op,
            $msg
        );
    }

    public static function parse($string)
    {
        $parser = new static();
        return $parser->parseQueryString($string);
    }

    protected function readNextKey()
    {
        $str = $this->readUnlessSpecialChar();

        if ($str === false) {
            return $str;
        }
        return rawurldecode($str);
    }

    protected function readNextValue()
    {
        if ($this->nextChar() === '(') {
            $this->readChar();
            $var = preg_split('~\|~', $this->readUnless(')'));
            if ($this->readChar() !== ')') {
                $this->parseError(null, 'Expected ")"');
            }
        } else {
            $var = rawurldecode($this->readUnless(array(')', '&', '|', '>', '<')));
        }
        return $var;
    }

    protected function readNextExpression()
    {
        if ('' === ($key = $this->readNextKey())) {
            return false;
        }

        foreach (array('<', '>') as $sign) {
            if (false !== ($pos = strpos($key, $sign))) {
                if ($this->nextChar() === '=') {
                    break;
                }
                $var = substr($key, $pos + 1);
                $key = substr($key, 0, $pos);
                return Filter::expression($key, $sign, $var);
            }
        }
        if (in_array($this->nextChar(), array('=', '>', '<', '!'))) {
            $sign = $this->readChar();
        } else {
            $sign = false;
        }
        if ($sign === false) {
            return Filter::expression($key, '=', true);
        }

        if ($sign === '=') {
            $last = substr($key, -1);
            if ($last === '>' || $last === '<') {
                $sign = $last . $sign;
                $key = substr($key, 0, -1);
            }
        // TODO: Same as above for unescaped <> - do we really need this?
        } elseif ($sign === '>' || $sign === '<' || $sign === '!') {
            if ($this->nextChar() === '=') {
                $sign .= $this->readChar();
            }
        }

        $var = $this->readNextValue();

        return Filter::expression($key, $sign, $var);
    }

    protected function parseError($char = null, $extraMsg = null)
    {
        if ($extraMsg === null) {
            $extra = '';
        } else {
            $extra = ': ' . $extraMsg;
        }
        if ($char === null) {
            $char = $this->string[$this->pos];
        }
        if ($this->reportDebug) {
            $extra .= "\n" . implode("\n", $this->debug);
        }

        throw new FilterParseException(
            'Invalid filter "%s", unexpected %s at pos %d%s',
            $this->string,
            $char,
            $this->pos,
            $extra
        );
    }

    protected function readFilters($nestingLevel = 0, $op = null)
    {
        $filters = array();
        while ($this->pos < $this->length) {
            if ($op === '!' && count($filters) === 1) {
                break;
            }
            $filter = $this->readNextExpression();
            $next = $this->readChar();


            if ($filter === false) {
                $this->debug('Got no next expression, next is ' . $next, $nestingLevel, $op);
                if ($next === '!') {
                    $not = $this->readFilters($nestingLevel + 1, '!');
                    $filters[] = $not;
                    if (in_array($this->nextChar(), array('|', '&', ')'))) {
                        $next = $this->readChar();
                        $this->debug('Got NOT, next is now: ' . $next, $nestingLevel, $op);
                    } else {
                        $this->debug('Breaking after NOT: ' . $not, $nestingLevel, $op);
                        break;
                    }
                }

                if ($op === null && count($filters) > 0 && ($next === '&' || $next === '|')) {
                    $op = $next;
                    continue;
                }

                if ($next === false) {
                    // Nothing more to read
                    break;
                }

                if ($next === ')') {
                    if ($nestingLevel > 0) {
                        $this->debug('Closing without filter: ' . $next, $nestingLevel, $op);
                        break;
                    }
                    $this->parseError($next);
                }
                if ($next === '(') {
                    $filters[] = $this->readFilters($nestingLevel + 1, null);
                    continue;
                }
                if ($next === $op) {
                    continue;
                }
                $this->parseError($next, "$op level $nestingLevel");
            } else {
                $this->debug('Got new expression: ' . $filter, $nestingLevel, $op);

                $filters[] = $filter;

                if ($next === false) {
                    $this->debug('Next is false, nothing to read but got filter', $nestingLevel, $op);
                    // Got filter, nothing more to read
                    break;
                }

                if ($op === '!') {
                    $this->pos--;
                    break;
                }
                if ($next === $op) {
                    $this->debug('Next matches operator', $nestingLevel, $op);
                    continue; // Break??
                }

                if ($next === ')') {
                    if ($nestingLevel > 0) {
                        $this->debug('Closing with filter: ' . $next, $nestingLevel, $op);
                        break;
                    }
                    $this->parseError($next);
                }
                if ($op === null && in_array($next, array('&', '|'))) {
                    $this->debug('Setting op to ' . $next, $nestingLevel, $op);
                    $op = $next;
                    continue;
                }
                $this->parseError($next);
            }
        }

        if ($nestingLevel === 0 && $this->pos < $this->length) {
            $this->parseError($op, 'Did not read full filter');
        }

        if ($nestingLevel === 0 && count($filters) === 1 && $op !== '!') {
            // There is only one filter expression, no chain
            $this->debug('Returning first filter only: ' . $filters[0], $nestingLevel, $op);
            return $filters[0];
        }

        if ($op === null && count($filters) === 1) {
            $this->debug('No op, single filter, setting AND', $nestingLevel, $op);
            $op = '&';
        }
        $this->debug(sprintf('Got %d filters, returning', count($filters)), $nestingLevel, $op);

        switch ($op) {
            case '&':
                return Filter::matchAll($filters);
            case '|':
                return Filter::matchAny($filters);
            case '!':
                return Filter::not($filters);
            case null:
                return Filter::matchAll();
            default:
                $this->parseError($op);
        }
    }

    protected function parseQueryString($string)
    {
        $this->pos = 0;

        $this->string = $string;

        $this->length = strlen($string);

        if ($this->length === 0) {
            return Filter::matchAll();
        }
        return $this->readFilters();
    }

    protected function readUnless($char)
    {
        $buffer = '';
        while (false !== ($c = $this->readChar())) {
            if (is_array($char)) {
                if (in_array($c, $char)) {
                    $this->pos--;
                    break;
                }
            } else {
                if ($c === $char) {
                    $this->pos--;
                    break;
                }
            }
            $buffer .= $c;
        }

        return $buffer;
    }

    protected function readUnlessSpecialChar()
    {
        return $this->readUnless(array('=', '(', ')', '&', '|', '>', '<', '!'));
    }

    protected function readExpressionOperator()
    {
        return $this->readUnless(array('=', '>', '<', '!'));
    }

    protected function readChar()
    {
        if ($this->length > $this->pos) {
            return $this->string[$this->pos++];
        }
        return false;
    }

    protected function nextChar()
    {
        if ($this->length > $this->pos) {
            return $this->string[$this->pos];
        }
        return false;
    }
}
