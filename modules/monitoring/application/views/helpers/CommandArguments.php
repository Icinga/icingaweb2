<?php

class Zend_View_Helper_CommandArguments extends Zend_View_Helper_Abstract
{
    public function commandArguments($command)
    {
        if (empty($command)) {
            return '';
        }
        $parts = explode('!', $command);
        $row = "<dd><b>%s</b>: %s</dd>\n";
        for ($i = 1; $i < count($parts); $i++) {
            $parts[$i] = sprintf($row, '$ARG' . $i . '$', $parts[$i]);
        }
        array_shift($parts);
        return "<dl>\n" . implode("\n", $parts) . "</dl>\n";
    }
}
