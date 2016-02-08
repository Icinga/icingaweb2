<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Cli;

use Icinga\Cli\Screen;
use Icinga\Exception\IcingaException;

// @see http://en.wikipedia.org/wiki/ANSI_escape_code

class AnsiScreen extends Screen
{
    protected $fgColors = array(
        'black'       => '30',
        'darkgray'    => '1;30',
        'red'         => '31',
        'lightred'    => '1;31',
        'green'       => '32',
        'lightgreen'  => '1;32',
        'brown'       => '33',
        'yellow'      => '1;33',
        'blue'        => '34',
        'lightblue'   => '1;34',
        'purple'      => '35',
        'lightpurple' => '1;35',
        'cyan'        => '36',
        'lightcyan'   => '1;36',
        'lightgray'   => '37',
        'white'       => '1;37',
    );

    protected $bgColors = array(
        'black'     => '40',
        'red'       => '41',
        'green'     => '42',
        'brown'     => '43',
        'blue'      => '44',
        'purple'    => '45',
        'cyan'      => '46',
        'lightgray' => '47',
    );

    public function strlen($string)
    {
        return strlen($this->stripAnsiCodes($string));
    }

    public function stripAnsiCodes($string)
    {
        return preg_replace('/\e\[?.*?[\@-~]/', '', $string);
    }

    public function clear()
    {
        return "\033[2J"   // Clear the whole screen
             . "\033[1;1H" // Move the cursor to row 1, column 1
             . "\033[1S";  // Scroll whole page up by 1 line (why?)
    }

    public function underline($text)
    {
        return "\033[4m"
          . $text
          . "\033[0m"; // Reset color codes
    }

    public function colorize($text, $fgColor = null, $bgColor = null)
    {
        return $this->startColor($fgColor, $bgColor)
            . $text
            . "\033[0m"; // Reset color codes
    }

    protected function fgColor($color)
    {
        if (! array_key_exists($color, $this->fgColors)) {
            throw new IcingaException(
                'There is no such foreground color: %s',
                $color
            );
        }
        return $this->fgColors[$color];
    }

    protected function bgColor($color)
    {
        if (! array_key_exists($color, $this->bgColors)) {
            throw new IcingaException(
                'There is no such background color: %s',
                $color
            );
        }
        return $this->bgColors[$color];
    }

    protected function startColor($fgColor = null, $bgColor = null)
    {
        $escape = "ESC[";
        $parts = array();
        if ($fgColor !== null
            && $bgColor !== null
            && ! array_key_exists($bgColor, $this->bgColors)
            && array_key_exists($bgColor, $this->fgColors)
            && array_key_exists($fgColor, $this->bgColors)
        ) {
            $parts[] = '7'; // reverse video, negative image
            $parts[] = $this->bgColor($fgColor);
            $parts[] = $this->fgColor($bgColor);
        } else {
            if ($fgColor !== null) {
                $parts[] = $this->fgColor($fgColor);
            }
            if ($bgColor !== null) {
                $parts[] = $this->bgColor($bgColor);
            }
        }
        if (empty($parts)) {
            return '';
        }
        return "\033[" . implode(';', $parts) . 'm';
    }
}
