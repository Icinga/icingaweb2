<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

use Icinga\Logger\Logger;
use Icinga\Data\Optional;
use Icinga\Data\The;
use Icinga\Filter\Query\Node;
use Icinga\Filter\Query\Tree;
use Icinga\Protocol\Statusdat;
use Icinga\Exception;
use Icinga\Data\SimpleQuery;
use Icinga\Protocol\Statusdat\Query as Query;
use Icinga\Protocol\Statusdat\View\AccessorStrategy;
use Icinga\Filter\Filterable;

/**
 * Class Query
 * @package Icinga\Backend\Statusdat
 */
abstract class StatusdatQuery extends Query implements Filterable, AccessorStrategy
{
    const TIMESTAMP = 'timestamp';

    /**
     * An array containing the mappi
     *
     * When implementing your own Mapper, this contains the static mapping rules.
     *
     * @see Icinga\Module\Monitoring\Backend\Statusdat\DataView\StatusdatServiceView for an example
     * @var array
     */
    public static $mappedParameters = array();

    /**
     * An array containing all properties that are retrieved by a function
     *
     * When implementing your own Mapper, this contains the handler for specific fields and allows you to lazy load
     * different fields if necessary. The methods are strings that will be mapped to methods of this class
     *
     * @var array
     * @see Icinga\Backend\Statusdat\DataView\StatusdatServiceView for an example
     */
    public static $handlerParameters = array();

    public static $fieldTypes = array();

    /**
     * @var null
     */
    private $cursor = null;


    public function init()
    {
        parent::init();
        $this->selectBase();
    }

    abstract public function selectBase();

    /**
     * Orders the resultset
     *
     * @param string $column    Either a string in the 'FIELD ASC/DESC format or only the field
     * @param null $dir 'asc' or 'desc'
     * @return Query            Returns this query,for fluent interface
     */
    public function order($column, $dir = null, $isFunction = false)
    {

        if ($column) {
            $column = strval($column);
            if (isset(static::$mappedParameters[$column])) {
                parent::order(static::$mappedParameters[$column], strtolower($dir));
            } elseif (isset(static::$handlerParameters[$column])) {
                parent::orderByFn(array($this, static::$handlerParameters[$column]), strtolower($dir));
            } else {
                Logger::info("Tried to sort by unknown column  %s", $column);
            }
        }
        return $this;
    }



    private $functionMap = array(
        "TO_DATE" => "toDateFormat"
    );



    /**
     *
     * @see Icinga\Backend\DataView\AccessorStrategy
     *
     * @param The $item
     * @param The $field
     * @return The|string
     * @throws \InvalidArgumentException
     */
    public function get(&$item, $field)
    {
        $result = null;
        if (isset($item->$field)) {
            $result = $item->$field;
        } elseif (isset(static::$mappedParameters[$field])) {
            $result = $this->getMappedParameter($item, $field);
        } elseif (isset(static::$handlerParameters[$field])) {
            $hdl = static::$handlerParameters[$field];
            $result = $this->$hdl($item);
        }

        return $result;
    }

    private function applyPropertyFunction($function, $value)
    {
        if (!isset($this->functionMap[$function])) {
            return $value;
        }
        $fn = $this->functionMap[$function];

        return $this->$fn($value);
    }

    private function toDateFormat($value)
    {
        if (is_numeric($value)) {
            return date("Y-m-d H:i:s", intval($value));
        } else {
            return $value;
        }
    }

    private function getMappedParameter(&$item, $field)
    {
        $matches = array();
        $fieldDef = static::$mappedParameters[$field];

        $function = false;
        if (preg_match_all('/(?P<FUNCTION>\w+)\((?P<PARAMETER>.*)\)/', $fieldDef, $matches)) {
            $function = $matches["FUNCTION"][0];
            $fieldDef = $matches["PARAMETER"][0];
        }
        $mapped = explode(".", $fieldDef);
        $res = $item;

        foreach ($mapped as $map) {
            if (is_array($res)) {
                $subResult = array();
                foreach ($res as $subitem) {
                    if (!isset($subitem->$map)) {
                        continue;
                    }
                    $subResult[] = $subitem->$map;
                }
                $res = join(',', $subResult);
            } else {
                if (!isset($res->$map)) {
                    return "";
                }
                $res = $res->$map;
            }
        }

        if ($function) {
            return $this->applyPropertyFunction($function, $res);
        }
        return $res;
    }

    /**
     *
     * @see Icinga\Backend\DataView\AccessorStrategy
     *
     * @param The $field
     * @return The|string
     */
    public function getMappedField($field)
    {
        if (isset(static::$mappedParameters[$field])) {
            return static::$mappedParameters[$field];
        }
        return $field;
    }

    /**
     *
     * @see Icinga\Backend\DataView\AccessorStrategy
     *
     * @param The $item
     * @param The $field
     * @return bool
     */
    public function exists(&$item, $field)
    {
        return (isset($item->$field)
            || isset(static::$mappedParameters[$field])
            || isset(static::$handlerParameters[$field])
        );
    }


    public function isValidFilterTarget($field)
    {
        return true;
    }

    public function isTimestamp($field)
    {
        return isset(static::$fieldTypes[$field]) && static::$fieldTypes[$field] === self::TIMESTAMP;
    }

}

