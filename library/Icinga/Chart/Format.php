<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart;

class Format
{
    /**
     * Format a number into a number-string as defined by the SVG-Standard
     *
     * @see http://www.w3.org/TR/SVG/types.html#DataTypeNumber
     *
     * @param $number
     *
     * @return string
     */
    public static function formatSVGNumber($number)
    {
        return number_format($number, 1, '.', '');
    }
}
