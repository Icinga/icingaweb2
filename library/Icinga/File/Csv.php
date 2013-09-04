<?php

namespace Icinga\File;

use Icinga\Data\AbstractQuery;

class Csv
{
    protected $query;

    protected function __construct()
    {
    }

    public static function fromQuery(AbstractQuery $query)
    {
        $csv = new Csv();
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
        foreach ($this->query->fetchAll() as $row) {
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
