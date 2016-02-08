<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Cli;

use Icinga\Cli\AnsiScreen;

class Screen
{
    protected static $instance;

    protected $isUtf8;

    public function getColumns()
    {
        $cols = (int) getenv('COLUMNS');
        if (! $cols) {
            // stty -a ?
            $cols = (int) exec('tput cols');
        }
        if (! $cols) {
            $cols = 80;
        }
        return $cols;
    }

    public function getRows()
    {
        $rows = (int) getenv('ROWS');
        if (! $rows) {
            // stty -a ?
            $rows = (int) exec('tput lines');
        }
        if (! $rows) {
            $rows = 25;
        }
        return $rows;
    }

    public function strlen($string)
    {
        return strlen($string);
    }

    public function newlines($count = 1)
    {
        return str_repeat("\n", $count);
    }

    public function center($txt)
    {
        $len = $this->strlen($txt);
        $width = floor(($this->getColumns() + $len) / 2) - $len;
        return str_repeat(' ', $width) . $txt;
    }

    public function hasUtf8()
    {
        if ($this->isUtf8 === null) {
            // null should equal 0 here, however seems to equal '' on some systems:
            $current = setlocale(LC_ALL, 0);

            $parts = preg_split('/;/', $current);
            $lc_parts = array();
            foreach ($parts as $part) {
                if (strpos($part, '=') === false) {
                    continue;
                }
                list($key, $val) = preg_split('/=/', $part, 2);
                $lc_parts[$key] = $val;
            }

            $this->isUtf8 = array_key_exists('LC_CTYPE', $lc_parts)
                && preg_match('~\.UTF-8$~i', $lc_parts['LC_CTYPE']);
        }
        return $this->isUtf8;
    }

    public function clear()
    {
        return "\n";
    }

    public function underline($text)
    {
        return $text;
    }

    public function colorize($text, $fgColor = null, $bgColor = null)
    {
        return $text;
    }

    public static function instance()
    {
        if (self::$instance === null) {
            if (function_exists('posix_isatty') && posix_isatty(STDOUT)) {
                self::$instance = new AnsiScreen();
            } else {
                self::$instance = new Screen();
            }
        }
        return self::$instance;
    }
}
