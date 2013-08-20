<?php

namespace Icinga\Module\Monitoring\Plugin;

class Perfdata
{
    const COUNTER = 0x01;
    const PERCENT = 0x02;
    const BYTES   = 0x04;
    const SECONDS = 0x08;

    protected $byte_map;

    protected $min;
    protected $max;
    protected $warn;
    protected $crit;
    protected $val;

    protected $unit;

    public function getFormattedValue()
    {
        switch ($this->unit) {
            case self::BYTES:
                return $this->formatBytes() . ' von ' . $this->formatBytes($this->max);
                break;
            case self::SECONDS:
                return $this->formatSeconds();
                break;
            case self::PERCENT:
                return number_format($this->val, 2, ',', '.') . '%';
                break;
            default:
                return $this->val;
        }
    }

    public function getValue()
    {
        return $this->val;
    }

    protected function formatBytes($val = null)
    {
        $steps = array(
            1 => 'Byte',
            1024 => 'KByte',
            1024 * 1024 => 'MByte',
            1024 * 1024 * 1024 => 'GByte',
            1024 * 1024 * 1024 * 1024 => 'TByte'
        );
        return $this->formatSpecial($steps, 1, $val);
    }

    protected function formatSeconds()
    {
        $steps = array(
            1        => 'us',
            1000     => 'ms',
            10000000 => 's',
        );
        return $this->formatSpecial($steps, 1000000, $this->val);
    }

    protected function formatSpecial($steps, $multi = 1, $val = null)
    {
        if ($val === null) {
            $val = abs($this->val);
        } else {
            $val = abs($val);
        }
        // TODO: Check this, prefix fails if $val is given
        if ($this->val < 0) {
            $prefix = '-';
        } else {
            $prefix = '';
        }
        $val *= $multi;
        $step = 1;
        foreach (array_keys($steps) as $key) {
            if ($key > $val * 1) {
                break;
            }
            $step = $key;
        }
        return $prefix
             . number_format($val / $step, 1, ',', '.')
            . ' '
            . $steps[$step];
    }

    protected function __construct(& $perfdata)
    {
        $this->byte_map = array(
            'b' => 1,
            'kb' => 1024,
            'mb' => 1024 * 1024,
            'gb' => 1024 * 1024 * 1024,
            'tb' => 1024 * 1024 * 1024 * 1024
        );

        // UGLY, fixes floats using comma:
        $perfdata = preg_replace('~\,~', '.', $perfdata);

        $parts = preg_split('~;~', $perfdata, 5);
        while (count($parts) < 5) {
            $parts[] = null;
        }
        list(
            $this->val,
            $this->warn,
            $this->crit,
            $this->min,
            $this->max
        ) = $parts;
        // TODO: check numbers!

        $unit = null;
        if (! preg_match('~^(\-?[\d+\.]+(?:E\-?\d+)?)([^\d]+)?$~', $this->val, $m)) {
            throw new \Exception('Got invalid perfdata: ' . $perfdata);
        }
        $this->val  = $m[1];
        if (isset($m[2])) {
            $unit = strtolower($m[2]);
        }
        if ($unit === 'c') {
            $this->unit = self::COUNTER;
        }

        if ($unit === '%') {
            if (! is_numeric($this->min)) {
                $this->min = 0;
            }
            if (! is_numeric($this->max)) {
                $this->max = 100;
            }
            $this->unit = self::PERCENT;
        } else {
            if (! is_numeric($this->max) && $this->crit > 0) {
                $this->max = $this->crit;
            }
        }


        if (array_key_exists($unit, $this->byte_map)) {
            $this->unit = self::BYTES;
            $this->val = $this->val * $this->byte_map[$unit];
            $this->min = $this->min * $this->byte_map[$unit];
            $this->max = $this->max * $this->byte_map[$unit];
        }
        if ($unit === 's') {
            $this->unit = self::SECONDS;
        }
        if ($unit === 'ms') {
            $this->unit = self::SECONDS;
            $this->val = $this->val / 1000;
        }
        if ($unit === '%') {
            if (! is_numeric($this->min)) {
                $this->min = 0;
            }
            if (! is_numeric($this->max)) {
                $this->max = 100;
            }
        } else {
            if (! is_numeric($this->max) && $this->crit > 0) {
                $this->max = $this->crit;
            }
        }
    }

    public function isCounter()
    {
        return $this->unit === self::COUNTER;
    }

    public static function fromString(& $perfdata)
    {
        $pdat = new Perfdata($perfdata);
        return $pdat;
    }

    protected function normalizeNumber($num)
    {
        return $num;
        // Bullshit, still TODO
        /*
        $dot = strpos($num, '.');
        $comma = strpos($num, ',');

        if ($dot === false) {
            // No dot...
            if ($comma === false) {
                // ...and no comma, it's an integer:
                return (int) $num;
            } else {
                // just a comma
            }
        } else {
            if ($comma === false) {
        }
        */
    }
}
