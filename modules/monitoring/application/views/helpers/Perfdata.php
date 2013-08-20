<?php

use \Icinga\Module\Monitoring\Plugin\PerfdataSet;

class Zend_View_Helper_Perfdata extends Zend_View_Helper_Abstract
{
    public function perfdata($perfdata, $compact = false, $float = 'right')
    {

        if (empty($perfdata)) {
            return '';
        }
        if ($float) {
            $float = ' style="float: ' . $float . '"';
        } else {
            $float = '';
        }

        $pset = PerfdataSet::fromString($perfdata);
        $ps = $pset->getAll();
        $perfdata = preg_replace('~\'([^\']+)\'~e', "str_replace(' ', '\'', '$1')", $perfdata);
        $parts = preg_split('~\s+~', $perfdata, -1, PREG_SPLIT_NO_EMPTY);

        $result = '';
        if ($compact === true) {
            $compact = 5;
        }
        if ($compact && count($parts) > $compact) {
            $parts = array_slice($parts, 0, $compact);
        }
        foreach ($parts as $part) {
            if (strpos($part, '=') === false) continue;
            list($name, $vals) = preg_split('~=~', $part, 2);
            $name = str_replace("'", ' ', $name);
            $parts = preg_split('~;~', $vals, 5);
            while (count($parts) < 5) $parts[] = null;
            list($val, $warn, $crit, $min, $max) = $parts;

            $unit = '';
            if (preg_match('~^([\d+\.]+)([^\d]+)$~', $val, $m)) {
                $unit = $m[2];
                $val = $m[1];
            } else {
                continue;
            }
            if ($unit == 'c') continue; // Counter pie graphs are not useful
            if ($unit == '%') {
                if (! $min ) $min = 0;
                if (! $max) $max = 100;
            } else {
                if (! $max && $crit > 0) $max = $crit;
                //return '';
            }
            if (! $max) continue;
            $green = 0;
            $orange = 0;
            $red = 0;
            $gray = $max - $val;
            if ($val < $warn) $green = $val;
            elseif ($val < $crit) $orange = $val;
            else $red = $val;
            if ($compact) {
                $result .= '<div class="inlinepie" title="' . htmlspecialchars($name) . ': ' .  htmlspecialchars($ps[$name]->getFormattedValue() /* $val*/)
                     // . htmlspecialchars($unit)
                     . '"' . $float . '>'
                     . implode(',', array($green, $orange, $red, $gray))
                     . '</div>';
            } else {
                $result .= '<tr><td><div class="inlinepie" title="' . htmlspecialchars($name) . '">'
                     . implode(',', array($green, $orange, $red, $gray))
                     . '</div></td><td>'
                     . htmlspecialchars($name)
                     . '</td><td>'
                     . htmlspecialchars($ps[$name]->getFormattedValue() /* $val*/)
                     //. htmlspecialchars($unit)
                     . '</td></tr>';
            }
        }
        if ($result == '') {
            $result = $perfdata;
        }
        if (! $compact && $result !== '') {
            $result = '<table style="width: 100%">' . $result . '</table>';
        }



        return $result;
    }
}
