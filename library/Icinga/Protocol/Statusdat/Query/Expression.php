<?php


namespace Icinga\Protocol\Statusdat\Query;

class Expression implements IQueryPart
{
    const ENC_NUMERIC = 0;
    const ENC_SET = 0;
    const ENC_STRING = 0;

    private $expression;
    private $field = null;
    private $basedata = array();
    private $function = null;
    private $value = "";
    private $operator = null;
    private $name = null;
    public $CB = null;

    private function getOperatorType($token)
    {
        switch (strtoupper($token)) {
            case ">":
                $this->CB = "IS_GREATER";
                break;
            case "<":
                $this->CB = "IS_LESS";
                break;
            case ">=":
                $this->CB = "IS_GREATER_EQ";
                break;
            case "<=":
                $this->CB = "IS_LESS_EQ";
                break;
            case "=":
                $this->CB = "IS_EQUAL";
                break;
            case "LIKE":
                $this->CB = "IS_LIKE";
                break;
            case "!=":
                $this->CB = "IS_NOT_EQUAL";
                break;
            case "IN":
                $this->CB = "IS_IN";
                break;

            default:
                throw new \Exception("Unknown operator $token in expression $this->expression !");
        }
    }


    private function extractAggregationFunction(&$tokens) {
        $token = $tokens[0];
        $value = array();
        if(preg_match("/COUNT\{(.*)\}/",$token,$value) == false)
            return $token;
        $this->function = "count";
        $tokens[0] = $value[1];
    }

    private function parseExpression(&$values)
    {
        $tokenized = preg_split("/ +/", trim($this->expression), 3);
        $this->extractAggregationFunction($tokenized);
        if (count($tokenized) != 3)
            echo ("Currently statusdat query expressions must be in the format FIELD OPERATOR ? or FIELD OPERATOR :value_name");

        $this->fields = explode(".",trim($tokenized[0]));
        $this->field = $this->fields[count($this->fields)-1];
        $this->getOperatorType(trim($tokenized[1]));
        $tokenized[2] = trim($tokenized[2]);

        if ($tokenized[2][0] === ":") {
            $this->name = substr($tokenized, 1);
            $this->value = $values[$this->name];
        } else if ($tokenized[2] === "?") {
            $this->value = array_shift($values);
        } else {
            $this->value = trim($tokenized[2]);
        }

    }

    public function fromString($expression, &$values)
    {
        $this->expression = $expression;
        $this->parseExpression($values);
        return $this;
    }

    public function __construct($expression = null, &$values = array())
    {
        if ($expression)
            $this->fromString($expression, $values);

    }

    public function filter(array &$base, &$idx = array())
    {
        if (!$idx)
            $idx = array_keys($base);
        $this->basedata = $base;
        return array_filter($idx, array($this,"filterFn"));
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getField()
    {
        return $this->field;
    }

    protected function filterFn($idx) {
        $values = $this->getFieldValues($idx);

        if($values === False)
            return false;

        if($this->CB == "IS_IN" ) {
            return count(array_intersect($values,$this->value)) > 0;
        }
        if($this->CB == "IS_NOT_IN" )
            return count(array_intersect($values,$this->value)) == 0;
        if($this->function) {
            $values = call_user_func($this->function,$values);
            if(!is_array($values))
                $values = array($values);
        }
        foreach($values as $val)
            if($this->{$this->CB}($val))
                return true;

        return false;
    }



    private function getFieldValues($idx) {
        $res = $this->basedata[$idx];
        foreach($this->fields as $field) {
            if(!is_array($res)) {
                if(!isset($res->$field)) {
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
            foreach($res as $sub) {
                if(!isset($sub->$field))
                    continue;
                if(!is_array($sub->$field))
                    $swap[] = $sub->$field;
                else {
                    $swap = array_merge($swap,$sub->$field);
                }
            }
            $res = $swap;
        }
        if(!is_array($res))
            return array($res);
        return $res;
    }

    public function IS_GREATER($value)
    {
        return $value > $this->value;
    }

    public function IS_LESS($value)
    {
        return $value < $this->value;
    }

    public function IS_LIKE($value)
    {

        return preg_match("/^".str_replace("%", ".*", $this->value)."$/", $value) ? true : false;
    }

    public function IS_EQUAL($value)
    {
        if(!is_numeric($value))
            return strtolower($value) ==strtolower($this->value);
        return $value == $this->value;
    }

    public function IS_NOT_EQUAL($value)
    {
        return $value != $this->value;
    }

    public function IS_GREATER_EQ($value)
    {
        return $value >= $this->value;
    }

    public function IS_LESS_EQ($value)
    {
        return $value <= $this->value;
    }



}