<?php

/**
 * Data Filter
 */
namespace Icinga\Data;

use ArrayIterator;

/**
 * This class contains an array of filters
 *
 * @package   Icinga\Data
 * @author    Icinga-Web Team <info@icinga.org>
 * @copyright 2013 Icinga-Web Team <info@icinga.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Filter extends ArrayIterator
{

    public function without($keys)
    {
        $filter = new Filter();
        $params = $this->toParams();
        if (! is_array($keys)) {
            $keys = array($keys);
        }
        foreach ($keys as $key) {
            if (array_key_exists($key, $params)) {
                unset($params[$key]);
            }
        }
        foreach ($params as $k => $v) {
            $filter[] = array($k, $v);
        }
        return $filter;
    }

    /**
     * Get filter as key-value array
     *
     * @return array
     */
    public function toParams()
    {
        $params = array();

        foreach ($this as $filter) {
            $params[$filter[0]] = $filter[1];
        }

        return $params;
    }
}
