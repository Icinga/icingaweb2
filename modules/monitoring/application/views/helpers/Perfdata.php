<?php

use Icinga\Module\Monitoring\Plugin\PerfdataSet;
use Icinga\Web\Widget\Chart\InlinePie;

class Zend_View_Helper_Perfdata extends Zend_View_Helper_Abstract
{
    public function perfdata($perfdata, $compact = false, $float = 'right')
    {

        if (empty($perfdata)) {
            return '';
        }
        if ($float) {
            $float = ' float: ' . $float . ';';
        } else {
            $float = '';
        }

        $pset = PerfdataSet::fromString($perfdata);
        $ps = $pset->getAll();
        $perfdata = preg_replace('~\'([^\']+)\'~e', "str_replace(' ', '\'', '$1')", $perfdata);
        $parts = preg_split('~\s+~', $perfdata, -1, PREG_SPLIT_NO_EMPTY);

        $table = array();
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
            if ($compact && $val < 0.0001) continue;
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
            $inlinePie = new InlinePie(array($green, $orange, $red, $gray));
            if ($compact) {
                $inlinePie->setTitle(htmlspecialchars($name) . ': ' . htmlspecialchars($ps[$name]->getFormattedValue()));
                $inlinePie->setStyle('float: right;');
                $result .= $inlinePie->render();
            } else {
                $inlinePie->setTitle(htmlspecialchars($name));
                $inlinePie->setStyle('float: left; margin: 0.2em 0.5em 0.2em 0;');
                $table[] = '<tr><th>' . $inlinePie->render()
                    . htmlspecialchars($name)
                    . '</th><td>'
                    . htmlspecialchars($ps[$name]->getFormattedValue()) .
                    '</td></tr>';
            }
        }
        if ($result == '' && ! $compact) {
            $result = $perfdata;
        }
        if (! empty($table)) {
            // TODO: What if we have both? And should we trust sprintf-style placeholders in perfdata titles?
            $result = '<table class="perfdata">' . implode("\n", $table) . '</table>';
        }

        return $result;
    }
}
