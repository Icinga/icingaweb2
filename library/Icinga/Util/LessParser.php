<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Util;

use Less_Tree_Anonymous;
use Less_Tree_Expression;
use Less_Tree_Quoted;
use Less_Tree_Value;
use lessc;

require_once 'lessphp/lessc.inc.php';

class LessParser extends lessc
{
    public function __construct()
    {
        $this->registerFunction('extract-variable-default', [$this, 'extractVariableDefault']);
    }

    /**
     * Extract default from given variable call
     *
     * How to use:
     *
     *   color: extract-variable-default(@mixin-parameter);
     *   color: @mixin-parameter;
     *
     *   border: extract-variable-default(1px solid @mixin-parameter);
     *   border: 1px solid @mixin-parameter;
     *
     *   background: drop-shadow(5px 0 3px extract-variable-default(@mixin-parameter, true));
     *   background: drop-shadow(5px 0 3px @mixin-parameter);
     *
     * @param mixed $value
     * @param bool $valAsDefault
     *
     * @return mixed
     */
    public function extractVariableDefault($value, $valAsDefault = false)
    {
        $defaultValue = 'inherit';
        if ($value instanceof Less_Tree_Quoted) {
            $stripped = preg_replace(
                '~var\s*\(\s*[-\w]+\s*,\s*([^)]+)\)~',
                '$1',
                $value->value,
                -1,
                $replacements
            );
            if ($replacements > 0) {
                $defaultValue = $stripped;
            }
        } elseif ($value instanceof Less_Tree_Expression) {
            foreach ($value->value as $i => $item) {
                $value->value[$i] = $this->extractVariableDefault($item, true);
            }

            return $value;
        }

        if ($valAsDefault && $defaultValue === 'inherit') {
            return $value;
        }

        return new Less_Tree_Value([new Less_Tree_Anonymous($defaultValue)]);
    }
}
