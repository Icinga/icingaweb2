<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\File;

use Icinga\Data\Browsable;

class Csv
{
    protected $query;

    protected function __construct() {}

    public static function fromQuery(Browsable $query)
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
        foreach ($this->query->getQuery()->fetchAll() as $row) {
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
