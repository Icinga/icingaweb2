<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use Traversable;

class Csv
{
    protected $query;

    protected function __construct()
    {
    }

    public static function fromQuery(Traversable $query)
    {
        $csv = new static();
        $csv->query = $query;
        return $csv;
    }

    public function dump()
    {
        header('Content-type: text/csv');
        echo (string) $this;
    }

    public function __toString()
    {
        $first = true;
        $csv = '';
        foreach ($this->query as $row) {
            if ($first) {
                $csv .= implode(',', array_keys((array) $row)) . "\r\n";
                $first = false;
            }
            $out = array();
            foreach ($row as & $val) {
                $out[] = '"' . $val . '"';
            }
            $csv .= implode(',', $out) . "\r\n";
        }

        return $csv;
    }
}
