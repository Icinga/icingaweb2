<?php

namespace Icinga\Module\Monitoring\Plugin;

class PerfdataSet
{
    protected $ptr;
    protected $pos = 0;
    protected $len;
    protected $perfdata;

    protected function __construct($perfdata)
    {
        if (empty($perfdata)) return;
        $this->ptr = & $perfdata;
        $this->len = strlen($this->ptr);
        while ($this->pos < $this->len) {
            $label = $this->readLabel();
            $perf  = $this->readUntil(' ');
            if (empty($perf)) continue;
            $this->perfdata[$label] = Perfdata::fromString($perf);
        }
    }

    public static function fromString(& $perfdata)
    {
        $pset = new PerfdataSet($perfdata);
        return $pset;
    }

    public function getAll()
    {
        return $this->perfdata;
    }

    protected function readLabel()
    {
        $this->skipSpaces();
        if (in_array($this->ptr[$this->pos], array('"', "'"))) {
            $this->pos++;
            $label = $this->readUntil($this->ptr[$this->pos - 1]);
            $this->pos++; // Skip ' or "
            $skip = $this->readUntil('=');
            $this->pos++;
        } else {
            $label = $this->readUntil('=');
            $this->pos++;
        }
        $this->skipSpaces();
        return trim($label);
    }

    protected function readUntil($stop_char)
    {
        $start = $this->pos;
        while ($this->pos < $this->len && $this->ptr[$this->pos] !== $stop_char) {
            $this->pos++;
        }
        return substr($this->ptr, $start, $this->pos - $start);
    }

    protected function skipSpaces()
    {
        while ($this->pos < $this->len && $this->ptr[$this->pos] === ' ') {
            $this->pos++;
        }
    }
}
