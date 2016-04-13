<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use stdClass;

/**
 * GLOB-like filter for simple data structures
 *
 * e.g. this filters:
 *
 * foo.bar.baz
 * foo.b*r.baz
 * **.baz
 *
 * match this one:
 *
 * array(
 *   'foo' => array(
 *     'bar' => array(
 *       'baz' => 'deadbeef'  // <---
 *     )
 *   )
 * )
 */
class GlobFilter
{
    /**
     * The prepared filters
     *
     * @var array
     */
    protected $filters;

    /**
     * Create a new filter from a comma-separated list of GLOB-like filters or an array of such lists.
     *
     * @param   string|\Traversable $filters
     */
    public function __construct($filters)
    {
        $patterns = array(array(''));
        $lastIndex1 = $lastIndex2 = 0;

        foreach ((is_string($filters) ? array($filters) : $filters) as $rawPatterns) {
            $escape = false;

            foreach (str_split($rawPatterns) as $c) {
                if ($escape) {
                    $escape = false;
                    $patterns[$lastIndex1][$lastIndex2] .= preg_quote($c, '/');
                } else {
                    switch ($c) {
                        case '\\':
                            $escape = true;
                            break;
                        case ',':
                            $patterns[] = array('');
                            ++$lastIndex1;
                            $lastIndex2 = 0;
                            break;
                        case '.':
                            $patterns[$lastIndex1][] = '';
                            ++$lastIndex2;
                            break;
                        case '*':
                            $patterns[$lastIndex1][$lastIndex2] .= '.*';
                            break;
                        default:
                            $patterns[$lastIndex1][$lastIndex2] .= preg_quote($c, '/');
                    }
                }
            }

            if ($escape) {
                $patterns[$lastIndex1][$lastIndex2] .= '\\\\';
            }
        }

        $this->filters = array();

        foreach ($patterns as $pattern) {
            foreach ($pattern as $i => $subPattern) {
                if ($subPattern === '') {
                    unset($pattern[$i]);
                } elseif ($subPattern === '.*.*') {
                    $pattern[$i] = '**';
                } elseif ($subPattern === '.*') {
                    $pattern[$i] = '/^' . $subPattern . '$/';
                } else {
                    $pattern[$i] = '/^' . trim($subPattern) . '$/i';
                }
            }

            if (! empty($pattern)) {
                $found = false;
                foreach ($pattern as $i => $v) {
                    if ($found) {
                        if ($v === '**') {
                            unset($pattern[$i]);
                        } else {
                            $found = false;
                        }
                    } elseif ($v === '**') {
                        $found = true;
                    }
                }

                if (end($pattern) === '**') {
                    $pattern[] = '/^.*$/';
                }

                $this->filters[] = array_values($pattern);
            }
        }
    }

    /**
     * Remove all keys/attributes matching any of $this->filters from $dataStructure
     *
     * @param   stdClass|array  $dataStructure
     *
     * @return  stdClass|array  The modified copy of $dataStructure
     */
    public function removeMatching($dataStructure)
    {
        foreach ($this->filters as $filter) {
            $dataStructure = static::removeMatchingRecursive($dataStructure, $filter);
        }
        return $dataStructure;
    }

    /**
     * Helper method for removeMatching()
     *
     * @param   stdClass|array  $dataStructure
     * @param   array           $filter
     *
     * @return  stdClass|array
     */
    protected static function removeMatchingRecursive($dataStructure, $filter)
    {
        $multiLevelPattern = $filter[0] === '**';
        if ($multiLevelPattern) {
            $dataStructure = static::removeMatchingRecursive($dataStructure, array_slice($filter, 1));
        }

        $isObject = $dataStructure instanceof stdClass;
        if ($isObject || is_array($dataStructure)) {
            if ($isObject) {
                $dataStructure = (array) $dataStructure;
            }

            if ($multiLevelPattern) {
                foreach ($dataStructure as $k => & $v) {
                    $v = static::removeMatchingRecursive($v, $filter);
                    unset($v);
                }
            } else {
                $currentLevel = $filter[0];
                $nextLevels = count($filter) === 1 ? null : array_slice($filter, 1);
                foreach ($dataStructure as $k => & $v) {
                    if (preg_match($currentLevel, (string) $k)) {
                        if ($nextLevels === null) {
                            unset($dataStructure[$k]);
                        } else {
                            $v = static::removeMatchingRecursive($v, $nextLevels);
                        }
                    }
                    unset($v);
                }
            }

            if ($isObject) {
                $dataStructure = (object) $dataStructure;
            }
        }

        return $dataStructure;
    }
}
